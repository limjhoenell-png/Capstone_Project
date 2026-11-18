<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogbookManualEntryStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_logbook_entry_is_saved_with_combined_name_and_address_and_shown_in_records(): void
    {
        $this->createLegacyLogbookTable();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.logbooks.store'), [
            'first_name' => 'Manual',
            'middle_name' => 'Local',
            'last_name' => 'Store Check',
            'name_extension' => 'Jr.',
            'person_category' => 'Resident',
            'region_code' => '11',
            'province_code' => '1124',
            'city_code' => '112403',
            'barangay_code' => '112403003',
            'purok_street' => '12 Test Street',
            'country_code' => '+63',
            'contact_number' => '09123456789',
            'purpose_choice' => 'Request for Barangay Clearance',
        ]);

        $response->assertRedirect(route('admin.barangay-logbook.logs'))
            ->assertSessionHas('status', 'Logbook entry created.');
        $this->assertDatabaseHas('logbook', [
            'full_name' => 'Manual Local Store Check Jr.',
            'person_classification' => 'Resident',
            'address' => '12 Test Street, San Jose, City of Digos, Davao del Sur, Region XI (Davao Region)',
            'contact_number' => '+63 09123456789',
            'purpose_of_transaction' => 'Request for Barangay Clearance',
            'transaction_type' => 'visitor',
            'represented_person_name' => '',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.logs'))
            ->assertOk()
            ->assertSee('Manual Local Store Check Jr.')
            ->assertSee('Resident')
            ->assertSee('+63 09123456789');
    }

    public function test_document_claim_saves_claimant_owner_document_notes_and_verification_details(): void
    {
        $this->createLegacyLogbookTable();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.logbooks.store'), [
            'transaction_type' => 'claim_documents_on_behalf',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Cruz',
            'name_extension' => 'Jr.',
            'person_category' => 'Visitor',
            'region_code' => '11',
            'province_code' => '1124',
            'city_code' => '112403',
            'barangay_code' => '112403003',
            'purok_street' => 'House 15',
            'country_code' => '+63',
            'contact_number' => '09123456789',
            'document_owner_first_name' => 'Juan',
            'document_owner_middle_name' => 'Dela',
            'document_owner_last_name' => 'Cruz',
            'document_owner_name_extension' => 'Sr.',
            'represented_person_classification' => 'Resident',
            'claim_document' => 'Request for Certificate of Residency',
            'additional_notes' => 'Bring original ID on pickup.',
            'authorization_letter_verified' => '1',
            'valid_id_verified' => '1',
        ]);

        $response->assertRedirect(route('admin.barangay-logbook.logs'))
            ->assertSessionHas('status', 'Logbook entry created.');
        $this->assertDatabaseHas('logbook', [
            'full_name' => 'Maria Santos Cruz Jr.',
            'person_classification' => 'Visitor',
            'purpose_of_transaction' => 'Request for Certificate of Residency',
            'transaction_type' => 'Claim Documents On Behalf',
            'is_authorized_representative' => true,
            'authorization_letter_verified' => true,
            'valid_id_verified' => true,
            'represented_person_name' => 'Juan Dela Cruz Sr.',
            'represented_person_classification' => 'Resident',
            'additional_notes' => 'Bring original ID on pickup.',
        ]);
    }

    public function test_document_claim_requires_both_verification_checkboxes(): void
    {
        $this->createLegacyLogbookTable();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->from(route('admin.logbooks.create'))
            ->post(route('admin.logbooks.store'), [
                'transaction_type' => 'claim_documents_on_behalf',
                'first_name' => 'Maria',
                'last_name' => 'Cruz',
                'person_category' => 'Visitor',
                'region_code' => '11',
                'province_code' => '1124',
                'city_code' => '112403',
                'barangay_code' => '112403003',
                'country_code' => '+63',
                'contact_number' => '09123456789',
                'document_owner_first_name' => 'Juan',
                'document_owner_last_name' => 'Cruz',
                'represented_person_classification' => 'Resident',
                'claim_document' => 'Request for Barangay Clearance',
            ])
            ->assertRedirect(route('admin.logbooks.create'))
            ->assertSessionHasErrors(['authorization_letter_verified', 'valid_id_verified']);
    }

    private function createLegacyLogbookTable(): void
    {
        Schema::create('logbook', function ($table): void {
            $table->increments('logbook_id');
            $table->string('full_name', 150);
            $table->string('address', 255);
            $table->string('contact_number', 50);
            $table->text('purpose_of_transaction');
            $table->integer('fingerprint_id')->nullable();
            $table->dateTime('time_in');
            $table->string('person_classification', 20)->default('Visitor');
            $table->string('transaction_type', 40)->default('visitor');
            $table->boolean('is_authorized_representative')->default(false);
            $table->boolean('authorization_letter_verified')->default(false);
            $table->boolean('valid_id_verified')->default(false);
            $table->string('represented_person_name', 150)->default('');
            $table->string('represented_person_classification', 30)->nullable();
            $table->text('additional_notes')->nullable();
        });
    }
}
