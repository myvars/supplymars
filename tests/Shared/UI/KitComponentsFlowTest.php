<?php

namespace App\Tests\Shared\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/**
 * The kit-based wrappers, form theme and layout as real pages use them: breadcrumb, pagination,
 * flash toasts on redirect and in Turbo Streams, the search box, the confirm dialog, form
 * addons, and the header's side panels and user menu.
 */
final class KitComponentsFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testPageBreadcrumbLinksBackToTheParentAndMarksTheCurrentPage(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Breadcrumb Manufacturer']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $manufacturer->getPublicId()->value())
            ->assertSuccessful()
            ->assertSeeIn('nav[data-slot="breadcrumb"] a[data-slot="breadcrumb-link"][href="/manufacturer/"][data-turbo-frame="body"]', 'Manufacturers')
            ->assertSeeElement('nav[data-slot="breadcrumb"] [data-slot="breadcrumb-separator"]')
            ->assertSeeIn('nav[data-slot="breadcrumb"] [data-slot="breadcrumb-page"][aria-current="page"]', 'Breadcrumb Manufacturer');
    }

    public function testResultsPaginationShowsTheCountAndKitPageLinks(): void
    {
        ManufacturerFactory::createMany(6);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/')
            ->assertSuccessful()
            ->assertSeeIn('[data-results-pagination]', 'of')
            // One navigation landmark: the kit's, not a second one wrapped around it.
            ->assertElementCount('[data-results-pagination] nav', 1)
            ->assertSeeIn('nav[data-slot="pagination"] [data-slot="pagination-link"][data-active="true"][aria-current="page"]', '1')
            ->assertSeeIn('nav[data-slot="pagination"] a[data-slot="pagination-link"][data-active="false"][href*="page=2"]', '2')
            ->assertSeeElement('nav[data-slot="pagination"] a[rel="next"][href*="page=2"]')
            ->assertSeeElement('nav[data-slot="pagination"] [aria-label="Go to previous page"][aria-disabled="true"]:not([href])');
    }

    public function testFlashAfterARedirectIsRenderedInsideThePageToaster(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/new')
            ->fillField('manufacturer[name]', 'Toast Manufacturer')
            ->click('Create Manufacturer')
            ->assertSuccessful()
            ->assertSeeIn('#flash-container[data-controller="sonner"] li[data-slot="toast"][data-type="success"][data-sonner-target="toast"] [data-slot="toast-title"]', 'Manufacturer created');
    }

    public function testFlashFromAnInlineEditIsStreamedIntoThePageToaster(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Old Name']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $manufacturer->getPublicId()->value() . '/inline/name?edit=1')
            // The inline form opts out of the kit theme: its field is a bare input, not a kit Input.
            ->assertSeeElement('input[name="inline_field[value]"]:not([data-slot])')
            ->fillField('inline_field[value]', 'New Name')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertSeeIn('turbo-stream[action="append"][target="flash-container"] template li[data-slot="toast"][data-sonner-target="toast"] [data-slot="toast-title"]', 'Updated successfully');
    }

    public function testSearchBoxIsAKitInputGroupWithTheFilterLinkAndEmptyState(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/?query=nothing-matches-this')
            ->assertSuccessful()
            ->assertSeeElement('form[data-controller="searchbox"] [data-slot="input-group"] input[data-slot="input-group-control"][name="query"][value="nothing-matches-this"][data-searchbox-target="queryInput"][autofocus]')
            ->assertSeeElement('[data-slot="input-group"] [data-slot="input-group-addon"][data-align="inline-end"] a#Category-search-filter[data-turbo-frame="modal"][aria-label^="Filter "]')
            ->assertSeeElement('[data-slot="empty"] [data-slot="empty-title"]');
    }

    public function testSearchBoxWithoutAFilterRouteHasNoFilterLink(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/')
            ->assertSuccessful()
            ->assertSeeElement('[data-slot="input-group"] input[data-slot="input-group-control"][name="query"]')
            ->assertNotSeeElement('[data-slot="input-group"] [data-slot="input-group-addon"][data-align="inline-end"]');
    }

    public function testDeleteConfirmationIsBuiltOnTheModalPanelAndPostsWithACsrfToken(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Confirm Manufacturer']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $manufacturer->getPublicId()->value() . '/delete/confirm', ['headers' => ['Turbo-Frame' => 'modal']])
            ->assertSuccessful()
            ->assertElementCount('h3#modal-title', 1)
            ->assertSeeElement('[data-modal-size="sm"] h3#modal-title')
            ->assertSeeElement('[data-modal-size="sm"] [data-slot="button"][data-action="basic-modal#close"][aria-label="Close dialog"]')
            ->assertSeeIn('[data-modal-size="sm"]', 'Are you sure you want to delete this Manufacturer?')
            ->assertSeeElement('[data-modal-size="sm"] form[method="post"] input[type="hidden"][name="_token"]')
            ->assertSeeElement('[data-modal-size="sm"] form button[type="submit"][data-variant="destructive-solid"]');
    }

    public function testFormThemeRendersKitFieldsWithErrorsLinkedToTheirControl(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/new')
            ->assertSeeElement('[data-slot="field"] [data-slot="field-label"][for="manufacturer_name"]')
            ->assertSeeElement('[data-slot="field"] input[data-slot="input"]#manufacturer_name')
            ->assertSeeElement('[data-slot="field"][data-orientation="horizontal"] [data-slot="checkbox"] input#manufacturer_isActive')
            ->click('Create Manufacturer')
            ->assertSeeElement('[data-slot="field"][data-invalid="true"] input#manufacturer_name[aria-invalid="true"][aria-describedby="manufacturer_name_error"]')
            ->assertSeeIn('#manufacturer_name_error', 'Please enter a manufacturer name');
    }

    public function testPercentAndMoneyFieldsShowTheirSymbolAsAnInputGroupAddon(): void
    {
        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());

        $browser
            ->get('/category/new')
            ->assertSuccessful()
            ->assertSeeElement('[data-slot="input-group"] input[data-slot="input-group-control"][name="category[defaultMarkup]"]')
            ->assertSeeIn('[data-slot="input-group"] [data-slot="input-group-addon"][data-align="inline-end"]', '%');

        $browser
            ->get('/supplier-product/new')
            ->assertSuccessful()
            ->assertSeeElement('[data-slot="input-group"] input[data-slot="input-group-control"][name="supplier_product[cost]"]')
            ->assertSeeIn('[data-slot="input-group"] [data-slot="input-group-addon"][data-align="inline-start"]', '£');
    }

    public function testDateFilterFieldsAreKitDatePickersThatSubmitThroughAHiddenInput(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/search/filter?startDate=2025-01-01')
            ->assertSuccessful()
            ->assertSeeIn('[data-slot="date-picker"][data-empty="false"] button#order_filter_startDate[data-slot="date-picker-trigger"] [data-slot="date-picker-value"]', '2025')
            ->assertSeeElement('[data-slot="date-picker"] [data-slot="calendar"] input[type="hidden"][name="order_filter[startDate]"][value="2025-01-01"]')
            ->assertSeeIn('[data-slot="date-picker"][data-empty="true"] button#order_filter_endDate [data-slot="date-picker-value"]', 'Any date')
            ->assertSeeElement('[data-slot="date-picker"] [data-slot="calendar"] input[type="hidden"][name="order_filter[endDate]"][value=""]')
            // These fields sit at the bottom of the filter modal, so their calendars open upwards.
            ->assertElementCount('[data-slot="date-picker"] [data-slot="popover-content"][data-side="top"]', 2);
    }

    public function testSidebarMenuIsAKitSheetWithCollapsibleSections(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/')
            ->assertSuccessful()
            ->assertSeeElement('[data-slot="sheet"][data-controller~="dialog"] > #navigationButton[data-dialog-target="trigger"][aria-haspopup="dialog"]')
            ->assertSeeElement('dialog#sheet-menu[data-slot="sheet-content"][data-side="left"][aria-labelledby="sheet-menu-title"]')
            ->assertSeeIn('#sheet-menu #sheet-menu-title', 'Main menu')
            // Links close the panel as they navigate, and so does its own close button.
            ->assertSeeElement('#sheet-menu nav[data-controller="sidebar-active"] a[href="/manufacturer/"][data-turbo-frame="body"][data-action="dialog#close"]')
            ->assertSeeElement('#sheet-menu [data-slot="button"][data-action="dialog#close"]')
            // Sections are opened and closed by sidebar-active, starting closed.
            ->assertSeeElement('#sheet-menu button[data-nav="section"][aria-controls="dropdown-catalog"][aria-expanded="false"][data-action="sidebar-active#toggle"]')
            ->assertSeeElement('#sheet-menu ul#dropdown-catalog.hidden')
            ->assertNotSeeElement('[data-collapse-toggle]')
            // The header bar is not itself a navigation landmark, so the menu's is not nested in one.
            ->assertNotSeeElement('nav nav')
            ->assertNotSeeElement('[data-controller~="basic-drawer"]');
    }

    public function testHelpPanelIsAKitSheetWithItsShortcutAndLazyFrame(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/')
            ->assertSuccessful()
            ->assertSeeElement('[data-slot="sheet"][data-controller~="dialog"][data-controller~="help-drawer"][data-action*="keydown@window->help-drawer#keydown"]')
            ->assertSeeElement('[data-controller~="help-drawer"] > [data-slot="button"][data-action="click->help-drawer#toggle"][data-dialog-target="trigger"]')
            ->assertSeeElement('dialog#sheet-help[data-side="right"] turbo-frame#help[loading="lazy"][data-help-drawer-target="frame"]');
    }

    public function testHeaderUserMenuIsAKitDropdownWithSettingsAndSignOut(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/manufacturer/')
            ->assertSuccessful()
            ->assertSeeElement('[data-controller="dropdown-menu"] [data-dropdown-menu-target="trigger"][aria-haspopup="menu"][aria-expanded="false"]')
            ->assertSeeIn('[data-controller="dropdown-menu"] [role="menu"] [data-slot="dropdown-menu-label"]', $user->getFullName())
            ->assertSeeIn('[data-controller="dropdown-menu"] [role="menu"] a[role="menuitem"][href^="/customer/"][data-turbo-frame="body"]', 'Settings')
            ->assertSeeIn('[data-controller="dropdown-menu"] [role="menu"] a[role="menuitem"][href="/logout"][data-turbo-prefetch="false"]', 'Sign Out');
    }
}
