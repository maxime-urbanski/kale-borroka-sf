<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\HomeShelf;
use App\Repository\HomepageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * What the homepage says, edited from the back office (« Page d'accueil »), with the strip
 * above the navbar and the footer's text, which every page shows. A single row; read it
 * through HomepageProviderInterface, which falls back to the defaults below.
 * Schema.org: WebPage; the label block is a WebPageElement (headline, alternativeHeadline
 * for the line on the paper strip, text, and a link to a CMS page or an address). The
 * footer's columns are FooterColumn rows.
 */
#[ORM\Entity(repositoryClass: HomepageRepository::class)]
class Homepage
{
    /**
     * A shop path or an http(s) address, without spaces. Not protocol-relative: browsers
     * read `//host` and `/\host` as another site.
     */
    public const string LINK_PATTERN = '#^(https?://|/(?![/\\\\]))\S*$#i';

    /** Full rows on a wide screen: 4 cards across for the productions, 6 for the articles. */
    public const array PRODUCTIONS_LIMITS = [4, 8, 12];
    public const array LATEST_LIMITS = [6, 12, 18];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    /** Black strip above the navbar, on wide screens. */
    #[ORM\Column(options: ['default' => true])]
    private bool $stripShown = true;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $stripLeft = 'Label & distro antifasciste depuis 2019';

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $stripRight = 'Punk · antifa · DIY';

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

    #[ORM\Column(options: ['default' => true])]
    private bool $productionsShown = true;

    #[ORM\Column(length: 60, options: ['default' => 'Dernières prod'])]
    #[Assert\NotBlank(message: 'Donnez un titre à la section.', normalizer: 'trim')]
    #[Assert\Length(max: 60)]
    private string $productionsTitle = 'Dernières prod';

    #[ORM\Column(options: ['default' => 4])]
    #[Assert\Choice(choices: self::PRODUCTIONS_LIMITS)]
    private int $productionsLimit = 4;

    /** Link to the label catalogue; empty: no link. */
    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $productionsLinkText = 'Tout le catalogue du label';

    #[ORM\Column(options: ['default' => true])]
    private bool $latestShown = true;

    #[ORM\Column(length: 60, options: ['default' => 'Derniers articles'])]
    #[Assert\NotBlank(message: 'Donnez un titre à la section.', normalizer: 'trim')]
    #[Assert\Length(max: 60)]
    private string $latestTitle = 'Derniers articles';

    #[ORM\Column(options: ['default' => 6])]
    #[Assert\Choice(choices: self::LATEST_LIMITS)]
    private int $latestLimit = 6;

    /** Link to the catalogue; empty: no link. */
    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $latestLinkText = 'Tous les arrivages';

    /**
     * Chips offered above the latest articles (HomeShelf values).
     *
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Choisissez au moins un rayon.')]
    private array $latestShelves = ['tout', 'vinyles', 'cd', 'k7', 'zines', 'occasion'];

    /** Under the label's name in the footer. Plain text; line breaks are kept. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $footerText = 'Label et distro indépendants. Punk, hardcore, oi! et tout ce qui fait du bruit contre l\'extrême droite. Fait main, sans patron.';

    /** After the copyright line, at the bottom of the footer. */
    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $footerTagline = 'Make racists afraid again';

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

    public function isStripShown(): bool
    {
        return $this->stripShown;
    }

    public function setStripShown(bool $stripShown): static
    {
        $this->stripShown = $stripShown;

        return $this;
    }

    public function getStripLeft(): ?string
    {
        return $this->stripLeft;
    }

    public function setStripLeft(?string $stripLeft): static
    {
        $this->stripLeft = self::nullIfBlank($stripLeft);

        return $this;
    }

    public function getStripRight(): ?string
    {
        return $this->stripRight;
    }

    public function setStripRight(?string $stripRight): static
    {
        $this->stripRight = self::nullIfBlank($stripRight);

        return $this;
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

    public function isProductionsShown(): bool
    {
        return $this->productionsShown;
    }

    public function setProductionsShown(bool $productionsShown): static
    {
        $this->productionsShown = $productionsShown;

        return $this;
    }

    public function getProductionsTitle(): string
    {
        return $this->productionsTitle;
    }

    public function setProductionsTitle(?string $productionsTitle): static
    {
        $this->productionsTitle = (string) $productionsTitle;

        return $this;
    }

    public function getProductionsLimit(): int
    {
        return $this->productionsLimit;
    }

    public function setProductionsLimit(int $productionsLimit): static
    {
        $this->productionsLimit = $productionsLimit;

        return $this;
    }

    public function getProductionsLinkText(): ?string
    {
        return $this->productionsLinkText;
    }

    public function setProductionsLinkText(?string $productionsLinkText): static
    {
        $this->productionsLinkText = self::nullIfBlank($productionsLinkText);

        return $this;
    }

    public function isLatestShown(): bool
    {
        return $this->latestShown;
    }

    public function setLatestShown(bool $latestShown): static
    {
        $this->latestShown = $latestShown;

        return $this;
    }

    public function getLatestTitle(): string
    {
        return $this->latestTitle;
    }

    public function setLatestTitle(?string $latestTitle): static
    {
        $this->latestTitle = (string) $latestTitle;

        return $this;
    }

    public function getLatestLimit(): int
    {
        return $this->latestLimit;
    }

    public function setLatestLimit(int $latestLimit): static
    {
        $this->latestLimit = $latestLimit;

        return $this;
    }

    public function getLatestLinkText(): ?string
    {
        return $this->latestLinkText;
    }

    public function setLatestLinkText(?string $latestLinkText): static
    {
        $this->latestLinkText = self::nullIfBlank($latestLinkText);

        return $this;
    }

    /**
     * @return list<HomeShelf> the chosen shelves, in the shop's order whatever the order picked
     */
    public function getLatestShelves(): array
    {
        return array_values(array_filter(
            HomeShelf::cases(),
            fn (HomeShelf $shelf): bool => \in_array($shelf->value, $this->latestShelves, true),
        ));
    }

    /**
     * @param list<HomeShelf> $latestShelves
     */
    public function setLatestShelves(array $latestShelves): static
    {
        $this->latestShelves = array_map(static fn (HomeShelf $shelf): string => $shelf->value, $latestShelves);

        return $this;
    }

    public function getFooterText(): ?string
    {
        return $this->footerText;
    }

    public function setFooterText(?string $footerText): static
    {
        $this->footerText = self::nullIfBlank($footerText);

        return $this;
    }

    public function getFooterTagline(): ?string
    {
        return $this->footerTagline;
    }

    public function setFooterTagline(?string $footerTagline): static
    {
        $this->footerTagline = self::nullIfBlank($footerTagline);

        return $this;
    }

    private static function nullIfBlank(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : $value;
    }
}
