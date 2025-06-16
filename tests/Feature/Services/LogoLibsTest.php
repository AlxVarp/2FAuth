<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\LogoLib\AbstractLogoLib;
use App\Services\LogoLib\DashboardiconsLogoLib;
use App\Services\LogoLib\LogoLibInterface;
use App\Services\LogoLib\SelfhLogoLib;
use App\Services\LogoLib\TfaLogoLib;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use Tests\Data\CommonDataProvider;
use Tests\Data\HttpRequestTestData;
use Tests\Data\OtpTestData;
use Tests\FeatureTestCase;

/**
 * LogoLibsTest test class
 */
#[CoversClass(AbstractLogoLib::class)]
#[CoversClass(SelfhLogoLib::class)]
#[CoversClass(DashboardiconsLogoLib::class)]
#[CoversClass(TfalogoLib::class)]
class LogoLibsTest extends FeatureTestCase
{
    use WithoutMiddleware;

    protected LogoLibInterface $logoLib;

    public function setUp() : void
    {
        parent::setUp();

        Storage::fake('icons');
        Storage::fake('logos');
        Storage::fake('imagesLink');
    }

    #[Test]
    #[DataProvider('provideLogoData')]
    public function test_getIcon_returns_stored_icon_file_in_requested_variant_when_logo_exists(array $logo)
    {
        Http::preventStrayRequests();
        Http::fake([
            $logo['url'] => Http::response($logo['svg'], 200),
        ]);

        $this->logoLib = $this->app->make($logo['class']);
        $icon          = $this->logoLib->getIcon('myservice', $logo['variant']);

        $this->assertNotNull($icon);
        Storage::disk('icons')->assertExists($icon);

        // Don't know why but with a faked storage, when an svg file is put to the disk, the svg is prefixed with an xml tag
        // so we prepend it manually for the assert to work as expected.
        $this->assertEquals(trim(Storage::disk('icons')->get($icon)), '<?xml version="1.0" encoding="UTF-8"?> ' . $logo['svg']);
    }

    /**
     * Provide Valid data for validation test
     */
    public static function provideLogoData() : array
    {
        return [
            'SELFH_REGULAR' => [[
                'variant' => 'regular',
                'url'     => CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice.svg',
                'svg'     => HttpRequestTestData::SVG_LOGO_BODY_VARIANT_REGULAR,
                'class'   => SelfhLogoLib::class,
            ]],
            'SELFH_LIGHT' => [[
                'variant' => 'light',
                'url'     => CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice-light.svg',
                'svg'     => HttpRequestTestData::SVG_LOGO_BODY_VARIANT_LIGHT,
                'class'   => SelfhLogoLib::class,
            ]],
            'SELFH_DARK' => [[
                'variant' => 'dark',
                'url'     => CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice-dark.svg',
                'svg'     => HttpRequestTestData::SVG_LOGO_BODY_VARIANT_DARK,
                'class'   => SelfhLogoLib::class,
            ]],
            'DASHBOARDICONS_REGULAR' => [[
                'variant' => 'regular',
                'url'     => CommonDataProvider::DASHBOARDICONS_URL_ROOT . 'svg/myservice.svg',
                'svg'     => HttpRequestTestData::SVG_LOGO_BODY_VARIANT_LIGHT,
                'class'   => DashboardiconsLogoLib::class,
            ]],
            'DASHBOARDICONS_LIGHT' => [[
                'variant' => 'light',
                'url'     => CommonDataProvider::DASHBOARDICONS_URL_ROOT . 'svg/myservice-light.svg',
                'svg'     => HttpRequestTestData::SVG_LOGO_BODY_VARIANT_LIGHT,
                'class'   => DashboardiconsLogoLib::class,
            ]],
            'DASHBOARDICONS_DARK' => [[
                'variant' => 'dark',
                'url'     => CommonDataProvider::DASHBOARDICONS_URL_ROOT . 'svg/myservice-dark.svg',
                'svg'     => HttpRequestTestData::SVG_LOGO_BODY_VARIANT_DARK,
                'class'   => DashboardiconsLogoLib::class,
            ]],
        ];
    }

    #[Test]
    public function test_getIcon_fetches_png_when_svg_is_not_available()
    {
        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice.svg' => Http::response('not found', 404),
            CommonDataProvider::SELFH_URL_ROOT . 'png/myservice.png' => Http::response(base64_decode(OtpTestData::ICON_PNG_DATA), 200),
        ]);

        $this->logoLib = $this->app->make(SelfhLogoLib::class);
        $icon          = $this->logoLib->getIcon('myservice');

        $this->assertStringEndsWith('.png', $icon);
    }

    #[Test]
    public function test_getIcon_fallbacks_to_regular_when_requested_variant_is_not_available()
    {
        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice-dark.svg'  => Http::response('not found', 404),
            CommonDataProvider::SELFH_URL_ROOT . 'png/myservice-dark.png'  => Http::response('not found', 404),
            CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice.svg' => Http::response(HttpRequestTestData::SVG_LOGO_BODY_VARIANT_REGULAR, 200),
        ]);

        $this->logoLib = $this->app->make(SelfhLogoLib::class);
        $icon          = $this->logoLib->getIcon('myservice', 'dark');

        $this->assertEquals(trim(Storage::disk('icons')->get($icon)), '<?xml version="1.0" encoding="UTF-8"?> ' . HttpRequestTestData::SVG_LOGO_BODY_VARIANT_REGULAR);
    }

    #[Test]
    public function test_getIcon_does_not_fallback_to_regular_when_requested_variant_is_not_available()
    {
        $user = User::factory()->create();
        $user['preferences->iconVariantStrictFetch'] = true;
        $user->save();

        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice-dark.svg'  => Http::response('not found', 404),
            CommonDataProvider::SELFH_URL_ROOT . 'png/myservice-dark.png'  => Http::response('not found', 404),
        ]);

        $this->logoLib = $this->app->make(SelfhLogoLib::class);
        $icon          = $this->actingAs($user)->logoLib->getIcon('myservice', 'dark');

        $this->assertNull($icon);
    }

    #[Test]
    public function test_getIcon_fallbacks_to_user_preferences_when_variant_is_omitted()
    {
        $user = User::factory()->create();
        $user['preferences->iconVariant'] = 'dark';
        $user->save();

        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice.svg' => Http::response(HttpRequestTestData::SVG_LOGO_BODY_VARIANT_REGULAR, 200),
            CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice-dark.svg'  => Http::response(HttpRequestTestData::SVG_LOGO_BODY_VARIANT_DARK, 200),
        ]);

        $this->logoLib = $this->app->make(SelfhLogoLib::class);
        $icon          = $this->actingAs($user)->logoLib->getIcon('myservice');

        $this->assertEquals(trim(Storage::disk('icons')->get($icon)), '<?xml version="1.0" encoding="UTF-8"?> ' . HttpRequestTestData::SVG_LOGO_BODY_VARIANT_DARK);
    }

    #[Test]
    #[DataProvider('provideVariantInvalidData')]
    public function test_getIcon_fallbacks_to_regular_when_variant_is_invalid_without_auth($variant)
    {
        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::SELFH_URL_ROOT . 'svg/myservice.svg' => Http::response(HttpRequestTestData::SVG_LOGO_BODY_VARIANT_REGULAR, 200),
        ]);

        $this->logoLib = $this->app->make(SelfhLogoLib::class);
        $icon          = $this->logoLib->getIcon('myservice', $variant);

        $this->assertNotNull($icon);
    }

    /**
     * Provide invalid variant data for validation test
     */
    public static function provideVariantInvalidData() : array
    {
        return [
            'INVALID_VARIANT' => [
                'not_a_valid_variant'
            ],
            'NULL_VARIANT' => [
                null
            ],
            'EMPTY_VARIANT' => [
                ''
            ],
        ];
    }

    #[Test]
    public function test_getIcon_returns_null_when_tfa_github_request_fails()
    {
        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::TFA_URL => Http::response(HttpRequestTestData::SVG_LOGO_BODY, 200),
            TfalogoLib::TFA_JSON_URL    => Http::response('not found', 404),
        ]);

        $this->logoLib = $this->app->make(TfalogoLib::class);
        $icon          = $this->logoLib->getIcon('service');

        $this->assertNull($icon);
    }

    #[Test]
    #[DataProviderExternal(CommonDataProvider::class, 'iconsCollectionProvider')]
    public function test_getIcon_returns_null_when_logo_fetching_fails(array $iconProvider)
    {
        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::TFA_URL            => Http::response('not found', 404),
            CommonDataProvider::SELFH_URL          => Http::response('not found', 404),
            CommonDataProvider::DASHBOARDICONS_URL => Http::response('not found', 404),
            TfalogoLib::TFA_JSON_URL               => Http::response(HttpRequestTestData::TFA_JSON_BODY, 200),
        ]);

        $this->logoLib = $this->app->make($iconProvider['class']);
        $icon          = $this->logoLib->getIcon('service');

        $this->assertNull($icon);
    }

    #[Test]
    public function test_getIcon_returns_null_when_no_logo_exists_in_the_tfa_collection()
    {
        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::TFA_URL => Http::response(HttpRequestTestData::SVG_LOGO_BODY, 200),
            TfalogoLib::TFA_JSON_URL    => Http::response(HttpRequestTestData::TFA_JSON_BODY, 200),
        ]);

        $this->logoLib = $this->app->make(TfalogoLib::class);
        $icon          = $this->logoLib->getIcon('no_logo_should_exists_with_this_name');

        $this->assertNull($icon);
    }

    #[Test]
    public function test_TfalogoLib_loads_empty_collection_when_tfajson_fetching_fails()
    {
        Http::preventStrayRequests();
        Http::fake([
            CommonDataProvider::TFA_URL => Http::response(HttpRequestTestData::SVG_LOGO_BODY, 200),
            TfalogoLib::TFA_JSON_URL    => Http::response('not found', 404),
        ]);

        $this->logoLib = $this->app->make(TfalogoLib::class);
        $icon          = $this->logoLib->getIcon('service');

        $this->assertNull($icon);
        Storage::disk('logos')->assertMissing(TfalogoLib::TFA_JSON);
    }
}
