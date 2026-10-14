<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HomepageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * What the homepage says, edited from the back office (« Page d'accueil »). A single row;
 * read it through HomepageProviderInterface, which falls back to the defaults below.
 * Schema.org: WebPage; the label block is a WebPageElement (headline, alternativeHeadline
 * for the line on the paper strip, text, and a link to a CMS page or an address).
 */
#[ORM\Entity(repositoryClass: HomepageRepository::class)]
class Homepage
{
    /**
     * A shop path or an http(s) address, without spaces. Not protocol-relative: browsers
     * read `//host` and `/\host` as another site.
     */
    public const string LINK_PATTERN = '#^(https?://|/(?![/\\\\]))\S*$#i';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $manifestoShown = true;

    /** Small line above the slogan. */
    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $manifestoKicker = 'Le label';

    /** The slogan, one line per row. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $manifestoHeadline = "Pas de patron.\nPas de pub.";

    /** Last line of the slogan, on a paper strip. */
    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $manifestoHighlight = 'Pas de fachos.';

    /** Plain text; line breaks are kept. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $manifestoText = 'On presse les disques des groupes qu\'on aime, on distribue ceux des copains et on fait tourner les zines. Chaque vente finance la prochaine sortie.';

    /** Red, green and white rule before the kicker. */
    #[ORM\Column(options: ['default' => true])]
    private bool $manifestoRule = true;

    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $manifestoLinkText = 'Lire le manifeste';

    /** Link target; the address below is used when it is empty. No link when unpublished. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Page $manifestoPage = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: self::LINK_PATTERN, message: 'Une adresse commençant par https:// ou une page du site commençant par / (sans espace).')]
    private ?string $manifestoUrl = null;

    #[Assert\Callback]
    public function validateSlogan(ExecutionContextInterface $context): void
    {
        if ($this->manifestoShown && [] === $this->getManifestoHeadlineLines() && null === $this->manifestoHighlight) {
            $context->buildViolation('Écrivez au moins une ligne de slogan.')
                ->atPath('manifestoHeadline')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isManifestoShown(): bool
    {
        return $this->manifestoShown;
    }

    public function setManifestoShown(bool $manifestoShown): static
    {
        $this->manifestoShown = $manifestoShown;

        return $this;
    }

    public function getManifestoKicker(): ?string
    {
        return $this->manifestoKicker;
    }

    public function setManifestoKicker(?string $manifestoKicker): static
    {
        $this->manifestoKicker = self::nullIfBlank($manifestoKicker);

        return $this;
    }

    public function getManifestoHeadline(): ?string
    {
        return $this->manifestoHeadline;
    }

    public function setManifestoHeadline(?string $manifestoHeadline): static
    {
        $this->manifestoHeadline = self::nullIfBlank($manifestoHeadline);

        return $this;
    }

    /**
     * @return list<string> the slogan's lines, trimmed, blank ones left out
     */
    public function getManifestoHeadlineLines(): array
    {
        $lines = array_map(trim(...), preg_split('/\R/', (string) $this->manifestoHeadline) ?: []);

        return array_values(array_filter($lines, static fn (string $line): bool => '' !== $line));
    }

    public function getManifestoHighlight(): ?string
    {
        return $this->manifestoHighlight;
    }

    public function setManifestoHighlight(?string $manifestoHighlight): static
    {
        $this->manifestoHighlight = self::nullIfBlank($manifestoHighlight);

        return $this;
    }

    public function getManifestoText(): ?string
    {
        return $this->manifestoText;
    }

    public function setManifestoText(?string $manifestoText): static
    {
        $this->manifestoText = self::nullIfBlank($manifestoText);

        return $this;
    }

    public function hasManifestoRule(): bool
    {
        return $this->manifestoRule;
    }

    public function setManifestoRule(bool $manifestoRule): static
    {
        $this->manifestoRule = $manifestoRule;

        return $this;
    }

    public function getManifestoLinkText(): ?string
    {
        return $this->manifestoLinkText;
    }

    public function setManifestoLinkText(?string $manifestoLinkText): static
    {
        $this->manifestoLinkText = self::nullIfBlank($manifestoLinkText);

        return $this;
    }

    public function getManifestoPage(): ?Page
    {
        return $this->manifestoPage;
    }

    public function setManifestoPage(?Page $manifestoPage): static
    {
        $this->manifestoPage = $manifestoPage;

        return $this;
    }

    public function getManifestoUrl(): ?string
    {
        return $this->manifestoUrl;
    }

    public function setManifestoUrl(?string $manifestoUrl): static
    {
        $this->manifestoUrl = self::nullIfBlank($manifestoUrl);

        return $this;
    }

    private static function nullIfBlank(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : $value;
    }
}
