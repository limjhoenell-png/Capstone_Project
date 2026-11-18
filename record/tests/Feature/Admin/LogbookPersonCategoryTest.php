<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogbookPersonCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_logbook_records_display_person_category_in_title_case(): void
    {
        $this->createLegacyLogbookTable('person_category');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        foreach (['personnel', 'visitor', 'resident'] as $category) {
            DB::table('logbook')->insert($this->logbookRecord($category));
        }

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.logs'))
            ->assertOk()
            ->assertSee('Person classification')
            ->assertSee('Personnel')
            ->assertSee('Visitor')
            ->assertSee('Resident')
            ->assertDontSee('<th>Entry type</th>', false)
            ->assertDontSee('<th>Fingerprint</th>', false)
            ->assertDontSee('Fingerprint ID')
            ->assertDontSee('device-1')
            ->assertDontSee('fonts.googleapis.com')
            ->assertDontSee('cdn.jsdelivr.net');
    }

    public function test_logbook_accounts_aggregate_profiles_by_recorded_classification(): void
    {
        $this->createLegacyLogbookTable('person_category');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            $this->logbookRecord('resident'),
            array_merge($this->logbookRecord('resident'), [
                'address' => 'Updated local address',
                'time_in' => '2026-11-02 11:00:00',
            ]),
            array_merge($this->logbookRecord('personnel'), ['full_name' => 'Personnel Profile']),
            array_merge($this->logbookRecord('visitor'), ['full_name' => 'Visitor Profile']),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.visitors.index'))
            ->assertOk()
            ->assertSee('Logbook Accounts')
            ->assertSee('Personnel')
            ->assertSee('Residents')
            ->assertSee('Visitor')
            ->assertSee('Test Resident')
            ->assertSee('Updated local address')
            ->assertSee('2');

        $this->actingAs($admin)
            ->get(route('admin.visitors.index', ['classification' => 'Resident', 'search' => 'Test Resident']))
            ->assertOk()
            ->assertSee('Test Resident')
            ->assertDontSee('Visitor Profile')
            ->assertDontSee('Personnel Profile');

        $this->actingAs($admin)
            ->get(route('admin.visitors.index', ['search' => 'Resident']))
            ->assertOk()
            ->assertSee('Test Resident')
            ->assertDontSee('Visitor Profile')
            ->assertDontSee('Personnel Profile');

        $this->actingAs($admin)
            ->get(route('admin.visitors.index', ['search' => 'x']))
            ->assertOk()
            ->assertSee('Test Resident')
            ->assertSee('Visitor Profile')
            ->assertSee('Personnel Profile');
    }

    public function test_logbook_account_profile_edit_updates_matching_records_and_classification(): void
    {
        $this->createLegacyLogbookTable('person_category');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            $this->logbookRecord('resident'),
            array_merge($this->logbookRecord('resident'), [
                'address' => 'Second address',
                'time_in' => '2026-11-02 11:00:00',
            ]),
            array_merge($this->logbookRecord('visitor'), ['full_name' => 'Other Visitor']),
        ]);
        $profileId = DB::table('logbook')->where('person_category', 'resident')->min('logbook_id');

        $this->actingAs($admin)
            ->put(route('admin.visitors.profiles.update', $profileId), [
                'profile_id' => $profileId,
                'full_name' => 'Updated Profile',
                'person_category' => 'Personnel',
                'address' => 'Updated address',
                'contact_number' => '555-0199',
            ])
            ->assertRedirect(route('admin.visitors.index', ['classification' => 'Personnel']))
            ->assertSessionHas('status', 'Logbook profile updated.');

        $this->assertSame(2, DB::table('logbook')
            ->where('full_name', 'Updated Profile')
            ->where('person_category', 'Personnel')
            ->where('address', 'Updated address')
            ->where('contact_number', '555-0199')
            ->count());
        $this->assertDatabaseHas('logbook', [
            'full_name' => 'Other Visitor',
            'person_category' => 'visitor',
        ]);
    }

    public function test_logbook_accounts_view_uses_debounced_search_and_profile_modal_without_saved_accounts_ui(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.visitors.index'))
            ->assertOk()
            ->assertSee('data-live-search', false)
            ->assertSee('setTimeout(refreshProfiles, 300)', false)
            ->assertSee('value="10" selected', false)
            ->assertDontSee('placeholder="Type at least 2 characters"', false)
            ->assertSee('data-per-page', false)
            ->assertSee('value="5"')
            ->assertSee('value="10"')
            ->assertSee('value="15"')
            ->assertSee('value="20"')
            ->assertSee('value="25"')
            ->assertSee('value="50"')
            ->assertSee('value="75"')
            ->assertSee('value="100"')
            ->assertSee('.accounts-pagination ul.pagination', false)
            ->assertSee('background: var(--c-surface)', false)
            ->assertSee('background: var(--c-primary)', false)
            ->assertDontSee('background: #0f172a', false)
            ->assertSee('list-style: none', false)
            ->assertSee('data-account-modal', false)
            ->assertSee('data-profile-id', false)
            ->assertSee('data-select-all-profiles', false)
            ->assertSee('data-profile-checkbox', false)
            ->assertSee('Delete Selected', false)
            ->assertSee('data-history-print-modal', false)
            ->assertSee('All Records')
            ->assertSee('Today / Specific Day')
            ->assertSee('This Week')
            ->assertSee('This Month / Specific Month')
            ->assertSee('Custom Date Range')
            ->assertSee('Generate &amp; Print', false)
            ->assertDontSee('Saved Logbook Accounts')
            ->assertDontSee('Save Account');
    }

    public function test_admin_can_bulk_delete_selected_logbook_profiles_and_all_matching_records(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            $this->logbookRecord('resident'),
            array_merge($this->logbookRecord('resident'), [
                'address' => 'Updated local address',
                'time_in' => '2026-11-02 11:00:00',
            ]),
            array_merge($this->logbookRecord('resident'), [
                'contact_number' => '555-0111',
            ]),
            array_merge($this->logbookRecord('visitor'), [
                'full_name' => 'Other Visitor',
            ]),
        ]);
        $selectedProfileId = DB::table('logbook')
            ->where('person_category', 'resident')
            ->where('contact_number', '555-0100')
            ->min('logbook_id');

        $this->actingAs($admin)
            ->delete(route('admin.visitors.bulk-destroy'), [
                'profile_ids' => [$selectedProfileId],
            ])
            ->assertRedirect(route('admin.visitors.index'))
            ->assertSessionHas('status', 'Deleted 1 logbook profile.');

        $this->assertSame(0, DB::table('logbook')
            ->where('person_category', 'resident')
            ->where('contact_number', '555-0100')
            ->count());
        $this->assertDatabaseHas('logbook', [
            'full_name' => 'Test Resident',
            'person_category' => 'resident',
            'contact_number' => '555-0111',
        ]);
        $this->assertDatabaseHas('logbook', [
            'full_name' => 'Other Visitor',
            'person_category' => 'visitor',
        ]);
    }

    public function test_bulk_delete_rejects_an_id_that_is_not_a_profile_representative(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            $this->logbookRecord('resident'),
            array_merge($this->logbookRecord('resident'), ['address' => 'Second record']),
        ]);
        $nonRepresentativeId = DB::table('logbook')->max('logbook_id');

        $this->actingAs($admin)
            ->delete(route('admin.visitors.bulk-destroy'), [
                'profile_ids' => [$nonRepresentativeId],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('profile_ids');

        $this->assertSame(2, DB::table('logbook')->count());
    }

    public function test_logbook_accounts_per_page_selection_limits_profiles_and_is_preserved_in_pagination(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        for ($index = 1; $index <= 8; $index++) {
            DB::table('logbook')->insert(array_merge($this->logbookRecord('visitor'), [
                'full_name' => sprintf('Profile %02d', $index),
                'time_in' => sprintf('2026-11-02 10:%02d:00', $index),
            ]));
        }

        $firstPage = $this->actingAs($admin)
            ->get(route('admin.visitors.index', ['per_page' => 5]));

        $firstPage->assertOk()
            ->assertSee('Profile 01')
            ->assertSee('Profile 05')
            ->assertDontSee('Profile 06')
            ->assertSee('per_page=5');

        $this->actingAs($admin)
            ->get(route('admin.visitors.index', ['per_page' => 5, 'profile_page' => 2]))
            ->assertOk()
            ->assertSee('Profile 06')
            ->assertDontSee('Profile 01');
    }

    public function test_logbook_reports_dashboard_links_accounts_from_sidebar_not_a_dashboard_card(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.index'))
            ->assertOk()
            ->assertSee('Logbook Accounts')
            ->assertDontSee('Visitor accounts');
    }

    public function test_logbook_json_returns_person_classification_as_person_category(): void
    {
        $this->createLegacyLogbookTable('person_classification');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert($this->logbookRecord('resident'));

        $this->actingAs($admin)
            ->getJson(route('admin.logbooks.index', ['per_page' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.person_category', 'Resident')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_admin_logbook_records_are_paginated_and_show_page_controls(): void
    {
        $this->createLegacyLogbookTable('person_category');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        for ($index = 1; $index <= 16; $index++) {
            DB::table('logbook')->insert(array_merge(
                $this->logbookRecord('visitor'),
                [
                    'full_name' => 'Visitor '.$index,
                    'time_in' => sprintf('2026-11-02 10:%02d:00', $index),
                ],
            ));
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.logs'));

        $response->assertOk()
            ->assertSee('Rows per page')
            ->assertSee('value="10" selected', false)
            ->assertSee('Previous')
            ->assertSee('Next');
        $this->assertSame(10, substr_count($response->getContent(), 'class="logbook-entry-select"'));

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.logs', ['page' => 2, 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Visitor 6')
            ->assertDontSee('Visitor 7');
    }

    public function test_logbook_print_includes_only_selected_legacy_entries(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            $this->logbookRecord('resident'),
            array_merge($this->logbookRecord('visitor'), [
                'full_name' => 'Unselected Visitor',
                'purpose_of_transaction' => 'Unselected purpose',
            ]),
        ]);
        $selectedId = DB::table('logbook')->where('full_name', 'Test Resident')->value('logbook_id');

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.print-records', ['selected_ids' => [$selectedId]]))
            ->assertOk()
            ->assertSee('BARANGAY SAN JOSE')
            ->assertSee('Test Resident')
            ->assertSee('Record test')
            ->assertSee('Recorded')
            ->assertDontSee('Unselected Visitor')
            ->assertDontSee('Unselected purpose');
    }

    public function test_logbook_print_search_applies_active_date_filters(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            array_merge($this->logbookRecord('resident'), [
                'purpose_of_transaction' => 'In range purpose',
                'time_in' => '2026-11-02 10:00:00',
            ]),
            array_merge($this->logbookRecord('resident'), [
                'purpose_of_transaction' => 'Out of range purpose',
                'time_in' => '2026-11-04 10:00:00',
            ]),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.print-records', [
                'search' => 'Test Resident',
                'from_date' => '2026-11-01',
                'to_date' => '2026-11-03',
            ]))
            ->assertOk()
            ->assertSee('2026-11-01 to 2026-11-03')
            ->assertSee('In range purpose')
            ->assertDontSee('Out of range purpose');
    }

    public function test_logbook_account_print_history_link_prints_that_profiles_records(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            $this->logbookRecord('resident'),
            array_merge($this->logbookRecord('resident'), [
                'purpose_of_transaction' => 'Second resident visit',
                'time_in' => '2026-11-03 10:00:00',
            ]),
            array_merge($this->logbookRecord('visitor'), [
                'full_name' => 'Other Visitor',
                'purpose_of_transaction' => 'Different visitor record',
            ]),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.visitors.index'))
            ->assertOk()
            ->assertSee('Print History')
            ->assertSee('data-profile-print', false);

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.print-records', [
                'name' => 'Test Resident',
                'classification' => 'Resident',
                'contact_number' => '555-0100',
            ]))
            ->assertOk()
            ->assertSee('Record test')
            ->assertSee('Second resident visit')
            ->assertDontSee('Different visitor record');
    }

    public function test_account_print_history_filters_a_specific_day_and_shows_the_selected_period(): void
    {
        $this->createLegacyLogbookTable('person_category');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert([
            array_merge($this->logbookRecord('resident'), [
                'purpose_of_transaction' => 'Selected day purpose',
                'time_in' => '2026-11-02 10:00:00',
            ]),
            array_merge($this->logbookRecord('resident'), [
                'purpose_of_transaction' => 'Other day purpose',
                'time_in' => '2026-11-03 10:00:00',
            ]),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.print-records', [
                'name' => 'Test Resident',
                'classification' => 'Resident',
                'contact_number' => '555-0100',
                'period' => 'day',
                'date' => '2026-11-02',
            ]))
            ->assertOk()
            ->assertSee('Period:</strong> November 2, 2026', false)
            ->assertSee('Selected day purpose')
            ->assertDontSee('Other day purpose');
    }

    public function test_admin_live_search_matches_name_classification_address_contact_and_purpose(): void
    {
        $this->createLegacyLogbookTable('person_classification');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DB::table('logbook')->insert(array_merge($this->logbookRecord('resident'), [
            'full_name' => 'Live Search Person',
            'address' => '22 Cypress Lane',
            'contact_number' => '555-0199',
            'purpose_of_transaction' => 'Certificate SearchPurpose',
        ]));
        DB::table('logbook')->insert(array_merge($this->logbookRecord('visitor'), [
            'full_name' => 'Unmatched Person',
            'address' => 'Other address',
            'contact_number' => '555-0101',
            'purpose_of_transaction' => 'Other purpose',
        ]));

        foreach ([
            'Live Search Person',
            'Resident',
            'Cypress Lane',
            '555-0199',
            'SearchPurpose',
        ] as $term) {
            $this->actingAs($admin)
                ->get(route('admin.barangay-logbook.logs', ['search' => $term]))
                ->assertOk()
                ->assertSee('Live Search Person')
                ->assertDontSee('Unmatched Person');
        }

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.logs', ['search' => 'x']))
            ->assertOk()
            ->assertSee('Live Search Person')
            ->assertSee('Unmatched Person');
    }

    private function createLegacyLogbookTable(string $categoryColumn): void
    {
        Schema::create('logbook', function ($table) use ($categoryColumn): void {
            $table->increments('logbook_id');
            $table->string('full_name')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('purpose_of_transaction')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->dateTime('time_in')->nullable();
            $table->string($categoryColumn)->nullable();
        });
    }

    /**
     * @return array<string, string>
     */
    private function logbookRecord(string $category): array
    {
        return [
            'full_name' => 'Test Resident',
            'address' => 'Local address',
            'contact_number' => '555-0100',
            'purpose_of_transaction' => 'Record test',
            'fingerprint_id' => 'device-1',
            'time_in' => '2026-11-02 10:00:00',
            (Schema::hasColumn('logbook', 'person_category') ? 'person_category' : 'person_classification') => $category,
        ];
    }
}
