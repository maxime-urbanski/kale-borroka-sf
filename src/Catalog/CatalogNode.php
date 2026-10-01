<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Typed read access to one mapping of the catalogue file. Every error names the path of the
 * faulty value (`albums[3].releases[0].stock`), so that the file can be fixed by hand.
 */
final readonly class CatalogNode
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        private array $data,
        public string $path,
    ) {
    }

    /**
     * Catches typos: a misspelt key would otherwise be silently ignored.
     *
     * @param list<string> $keys
     */
    public function allowOnly(array $keys): void
    {
        $unknown = array_diff(array_map(strval(...), array_keys($this->data)), $keys);

        if ([] !== $unknown) {
            throw $this->error(\sprintf('clé(s) inconnue(s) « %s », attendu : %s', implode(', ', $unknown), implode(', ', $keys)));
        }
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function string(string $key): string
    {
        return $this->optionalString($key) ?? throw $this->error('valeur obligatoire', $key);
    }

    public function optionalString(string $key): ?string
    {
        $value = $this->get($key);

        if (null === $value) {
            return null;
        }

        return $this->text($value, $key);
    }

    public function int(string $key): int
    {
        return $this->optionalInt($key) ?? throw $this->error('valeur obligatoire', $key);
    }

    public function optionalInt(string $key): ?int
    {
        $value = $this->get($key);

        if (null !== $value && !\is_int($value)) {
            throw $this->error('nombre entier attendu', $key);
        }

        return $value;
    }

    public function bool(string $key, bool $default): bool
    {
        $value = $this->get($key) ?? $default;

        if (!\is_bool($value)) {
            throw $this->error('true ou false attendu', $key);
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    public function strings(string $key): array
    {
        $strings = [];

        foreach ($this->list($key) as $index => $value) {
            $strings[] = $this->text($value, \sprintf('%s[%d]', $key, $index)) ?? throw $this->error('texte attendu', \sprintf('%s[%d]', $key, $index));
        }

        return $strings;
    }

    public function node(string $key): self
    {
        $value = $this->get($key) ?? [];

        if (!\is_array($value)) {
            throw $this->error('liste de clés attendue', $key);
        }

        return new self($value, $this->childPath($key));
    }

    /**
     * @return array<string, self>
     */
    public function nodes(string $key): array
    {
        $nodes = [];

        foreach ($this->node($key)->data as $childKey => $value) {
            $childPath = \sprintf('%s.%s', $this->childPath($key), $childKey);

            if (!\is_array($value)) {
                throw new CatalogImportException($childPath.' : liste de clés attendue');
            }
            $nodes[(string) $childKey] = new self($value, $childPath);
        }

        return $nodes;
    }

    /**
     * @return list<self>
     */
    public function items(string $key): array
    {
        $items = [];

        foreach ($this->list($key) as $index => $value) {
            $childPath = \sprintf('%s[%d]', $this->childPath($key), $index);

            if (!\is_array($value)) {
                throw new CatalogImportException($childPath.' : liste de clés attendue');
            }
            $items[] = new self($value, $childPath);
        }

        return $items;
    }

    public function error(string $message, ?string $key = null): CatalogImportException
    {
        return new CatalogImportException(\sprintf('%s : %s', null === $key ? $this->path : $this->childPath($key), $message));
    }

    /**
     * YAML reads `title: 1984` as an integer, taken as the text it is. A float is refused:
     * `catalogNumber: 1.10` would come back as « 1.1 ».
     */
    private function text(mixed $value, string $key): ?string
    {
        if (\is_int($value)) {
            $value = (string) $value;
        }

        if (!\is_string($value)) {
            throw $this->error(\is_float($value) ? 'nombre à virgule : mettez-le entre guillemets' : 'texte attendu', $key);
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    /**
     * @return list<mixed>
     */
    private function list(string $key): array
    {
        $value = $this->get($key) ?? [];

        if (!\is_array($value) || !array_is_list($value)) {
            throw $this->error('liste attendue', $key);
        }

        return $value;
    }

    private function childPath(string $key): string
    {
        return '' === $this->path ? $key : $this->path.'.'.$key;
    }
}
