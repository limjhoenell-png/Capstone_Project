<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogbookImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_only_import_saves_missing_values_as_null_and_renders_them_blank(): void
    {
        $this->createLegacyLogbookTable();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->postJson(route('admin.logbooks.import'), [
                'rows' => json_encode([
                    ['Name of User' => 'A. User'],
                ], JSON_THROW_ON_ERROR),
            ])
            ->assertOk()
            ->assertJsonPath('result.inserted', 1)
            ->assertJsonPath('result.errors', 0);

        $this->assertDatabaseHas('logbook', [
            'full_name' => 'A. User',
            'address' => null,
            'contact_number' => null,
            'purpose_of_transaction' => null,
            'person_category' => null,
            'transaction_type' => null,
            'is_authorized_representative' => null,
            'authorization_letter_verified' => null,
            'valid_id_verified' => null,
            'represented_person_name' => null,
            'fingerprint_id' => null,
        ]);

        $response = $this->get(route('admin.barangay-logbook.logs'))
            ->assertOk()
            ->assertSee('A. User');

        $this->assertStringContainsString('class="logbook-td-user">', $response->getContent());
    }

    public function test_contact_only_import_is_rejected(): void
    {
        $this->createLegacyLogbookTable();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->postJson(route('admin.logbooks.import'), [
                'rows' => json_encode([
                    ['Contact Number' => '09266952238'],
                ], JSON_THROW_ON_ERROR),
            ])
            ->assertOk()
            ->assertJsonPath('result.inserted', 0)
            ->assertJsonPath('result.errors', 1);

        $this->assertDatabaseCount('logbook', 0);
    }

    private function createLegacyLogbookTable(): void
    {
        Schema::create('logbook', function ($table): void {
            $table->increments('logbook_id');
            $table->string('full_name')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('purpose_of_transaction')->nullable();
            $table->string('person_category')->nullable();
            $table->string('transaction_type')->nullable();
            $table->boolean('is_authorized_representative')->nullable();
            $table->boolean('authorization_letter_verified')->nullable();
            $table->boolean('valid_id_verified')->nullable();
            $table->string('represented_person_name')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->dateTime('time_in')->nullable();
        });
    }
}
