<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogbookBulkDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_only_selected_legacy_logbook_entries(): void
    {
        Schema::create('logbook', function ($table): void {
            $table->increments('logbook_id');
            $table->string('full_name')->nullable();
        });

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $selectedIds = DB::table('logbook')->insertGetId(['full_name' => 'Selected'], 'logbook_id');
        $remainingId = DB::table('logbook')->insertGetId(['full_name' => 'Keep'], 'logbook_id');

        $this->actingAs($admin)
            ->delete(route('admin.logbooks.bulk-destroy'), [
                'selected_ids' => [$selectedIds],
            ])
            ->assertRedirect(route('admin.barangay-logbook.logs'))
            ->assertSessionHas('status', 'Deleted 1 logbook entry.');

        $this->assertDatabaseMissing('logbook', ['logbook_id' => $selectedIds]);
        $this->assertDatabaseHas('logbook', ['logbook_id' => $remainingId]);
    }
}
