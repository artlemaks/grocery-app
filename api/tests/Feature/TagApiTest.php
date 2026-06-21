<?php

namespace Tests\Feature;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithHousehold;
use Tests\TestCase;

class TagApiTest extends TestCase
{
    use RefreshDatabase, WithHousehold;

    public function test_store_creates_a_tag_scoped_to_the_household(): void
    {
        $this->actingInHousehold();

        $this->postJson('/api/v1/tags', ['name' => 'Quick'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Quick');

        $this->assertDatabaseHas('tags', [
            'name' => 'Quick',
            'household_id' => $this->household->id,
        ]);
    }

    public function test_index_lists_only_the_current_households_tags(): void
    {
        $this->actingInHousehold();
        Tag::create(['name' => 'Quick']);
        Tag::create(['name' => 'Vegan']);

        // Another household's tag.
        Tag::factory()->create(['name' => 'Foreign']);

        $response = $this->getJson('/api/v1/tags');

        $response->assertOk()->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Quick', 'Vegan'], $names);
    }

    public function test_duplicate_tag_name_in_the_same_household_is_rejected(): void
    {
        $this->actingInHousehold();
        Tag::create(['name' => 'Quick']);

        $this->postJson('/api/v1/tags', ['name' => 'Quick'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_unauthenticated_index_request_is_rejected(): void
    {
        $this->getJson('/api/v1/tags')->assertUnauthorized();
    }
}
