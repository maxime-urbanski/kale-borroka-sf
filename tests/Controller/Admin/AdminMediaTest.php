<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Album;
use App\Entity\Image;
use App\Entity\MediaObject;
use App\Entity\SocialNetwork;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Album pictures ("Visuels" tab) and the media library. Uploads really happen, into
 * var/test-uploads (see config/packages/vich_uploader.yaml), emptied after each test.
 */
class AdminMediaTest extends WebTestCase
{
    use OrderTestTrait;

    /** A valid 1×1 PNG. */
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        (new Filesystem())->remove(self::uploadDir());
        parent::tearDown();
    }

    public function testAlbumFormIsSplitIntoTabs(): void
    {
        $crawler = $this->client->request('GET', '/admin/album/new');

        self::assertResponseIsSuccessful();
        $tabs = array_map('trim', $crawler->filter('.nav-tabs .nav-link')->each(fn ($tab) => $tab->text()));
        self::assertSame(['Infos', 'Tracklist', 'Pressages', 'Visuels'], $tabs);
    }

    public function testPicturesAreUploadedFromTheAlbumFormAndOrdered(): void
    {
        $this->submitAlbumForm('/admin/album/new', ['name' => 'Album illustré', 'artist' => ['autocomplete' => '__new__:Groupe Visuel'], 'releaseType' => 'album'], [
            ['position' => '2', 'file' => $this->png('verso.png')],
            ['position' => '1', 'file' => $this->png('recto.png')],
        ]);

        $album = $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Album illustré']);
        self::assertInstanceOf(Album::class, $album);
        self::assertCount(2, $album->getImages());
        self::assertStringStartsWith('recto', (string) $album->getCoverImageName(), 'the lowest position is the cover');
        self::assertFileExists(self::uploadDir().'/albums/'.$album->getCoverImageName());
    }

    public function testRemovingAPictureDeletesItWhenNothingElseUsesIt(): void
    {
        $this->submitAlbumForm('/admin/album/new', ['name' => 'Album à nettoyer', 'artist' => ['autocomplete' => '__new__:Groupe Ménage'], 'releaseType' => 'album'], [
            ['position' => '1', 'file' => $this->png('cover.png')],
        ]);
        $album = $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Album à nettoyer']);
        $image = $album?->getImages()->first();
        self::assertInstanceOf(Image::class, $image);
        [$imageId, $file] = [$image->getId(), self::uploadDir().'/albums/'.$image->getImageName()];
        self::assertFileExists($file);

        // Submit the edit form without the picture entry.
        self::assertNotNull($album);
        $form = $this->client->request('GET', \sprintf('/admin/album/%d/edit', $album->getId()))->filter('form[name="Album"]')->form();
        $values = $form->getPhpValues();
        unset($values['Album']['images']);
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        self::assertNull($this->entityManager()->getRepository(Image::class)->find($imageId));
        self::assertFileDoesNotExist($file);
    }

    public function testMediaLibraryUploadsAndReplacesFiles(): void
    {
        $crawler = $this->client->request('GET', '/admin/media-object/new');
        $form = $crawler->filter('form[name="MediaObject"]')->form();
        $this->client->request('POST', $form->getUri(), $form->getPhpValues(), ['MediaObject' => ['file' => ['file' => $this->png('icone.png')]]]);
        self::assertResponseRedirects();

        $media = $this->entityManager()->getRepository(MediaObject::class)->findOneBy([], ['id' => 'DESC']);
        self::assertInstanceOf(MediaObject::class, $media);
        self::assertStringStartsWith('icone', (string) $media->getFilename());
        self::assertStringNotContainsString('/', (string) $media->getFilename(), 'the bare stored name, not a URI');
        self::assertFileExists(self::uploadDir().'/media/'.$media->getFilename());
        $firstName = $media->getFilename();

        // Replacing the file on edit used to be ignored: nothing mapped changed.
        $form = $this->client->request('GET', \sprintf('/admin/media-object/%d/edit', $media->getId()))->filter('form[name="MediaObject"]')->form();
        $this->client->request('POST', $form->getUri(), $form->getPhpValues(), ['MediaObject' => ['file' => ['file' => $this->png('nouvelle-icone.png')]]]);
        self::assertResponseRedirects();

        $media = $this->entityManager()->find(MediaObject::class, $media->getId());
        self::assertStringStartsWith('nouvelle-icone', (string) $media?->getFilename());
        self::assertFileDoesNotExist(self::uploadDir().'/media/'.$firstName);
    }

    public function testAMediaUsedBySocialNetworkIsNotDeletedAndShowsInTheFooter(): void
    {
        $media = (new MediaObject())->setFilename('instagram.png');
        $socialNetwork = (new SocialNetwork())->setName('Instagram')->setUrl('https://instagram.com/kbr')->setIsPublish(true)->setInFooter(true)->setFile($media);
        $this->entityManager()->persist($media);
        $this->entityManager()->persist($socialNetwork);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/');
        self::assertSame('/media/instagram.png', $crawler->filter('footer img[alt="Instagram"]')->attr('src'));

        $crawler = $this->client->request('GET', '/admin/media-object');
        $token = $crawler->filter('form#delete-form input[name="token"], input[name="token"]')->first()->attr('value');
        $this->client->request('POST', \sprintf('/admin/media-object/%d/delete', $media->getId()), ['token' => $token]);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-danger', 'Instagram');
        self::assertNotNull($this->entityManager()->find(MediaObject::class, $media->getId()));
    }

    /**
     * @param array<string, mixed>                              $fields
     * @param list<array{position: string, file: UploadedFile}> $pictures
     */
    private function submitAlbumForm(string $uri, array $fields, array $pictures): void
    {
        $form = $this->client->request('GET', $uri)->filter('form[name="Album"]')->form();
        $values = $form->getPhpValues();
        $files = [];

        foreach ($fields as $name => $value) {
            $values['Album'][$name] = $value;
        }
        foreach ($pictures as $i => $picture) {
            $values['Album']['images'][$i] = ['position' => $picture['position']];
            $files['Album']['images'][$i] = ['imageFile' => ['file' => $picture['file']]];
        }

        $this->client->request('POST', $form->getUri(), $values, $files);
        self::assertResponseRedirects(null, null, 'the album form should be valid');
    }

    private function png(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'kbr');
        file_put_contents($path, base64_decode(self::PNG, true));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private static function uploadDir(): string
    {
        return \dirname(__DIR__, 3).'/var/test-uploads';
    }
}
