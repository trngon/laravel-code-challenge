<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        $cardNumber = rand(1000000000000000, 9999999999999999);
        $this->user->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $this->get('api/debit-cards')
            ->assertStatus(200)
            ->assertSeeText($cardNumber);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $anotherUser = User::factory()->create();
        $cardNumber = rand(1000000000000000, 9999999999999999);

        $anotherUser->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $this->get('api/debit-cards')
            ->assertStatus(200)
            ->assertDontSeeText($cardNumber);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $this->post('api/debit-cards', ['type' => 'Visa'])
            ->assertStatus(201);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'Visa',
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $cardNumber = rand(1000000000000000, 9999999999999999);
        $cardInfo = $this->user->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $this->get('api/debit-cards/' . $cardInfo->id)
            ->assertStatus(200)
            ->assertSeeText($cardNumber);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $anotherUser = User::factory()->create();
        $cardNumber = rand(1000000000000000, 9999999999999999);

        $cardInfo = $anotherUser->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $this->get('api/debit-cards/' . $cardInfo->id)
            ->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $cardNumber = rand(1000000000000000, 9999999999999999);
        $cardInfo = $this->user->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'is_active' => false,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        //Active the Card
        $this->put('api/debit-cards/' . $cardInfo->id, [
            'is_active' => true
        ])->assertStatus(200);

        $newCardInfo = $this->user->debitCards()->find($cardInfo->id);

        $this->assertNull($newCardInfo->disabled_at);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $cardNumber = rand(1000000000000000, 9999999999999999);
        $cardInfo = $this->user->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        //De-active the Card
        $this->put('api/debit-cards/' . $cardInfo->id, [
            'is_active' => false
        ])->assertStatus(200);

        $newCardInfo = $this->user->debitCards()->find($cardInfo->id);

        $this->assertNotNull($newCardInfo->disabled_at);

    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $anotherUser = User::factory()->create();
        $cardNumber = rand(1000000000000000, 9999999999999999);

        $cardInfo = $anotherUser->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $this->put('api/debit-cards/' . $cardInfo->id, [
            'is_active' => false
        ])
            ->assertStatus(403);

        $newCardInfo = DebitCard::find($cardInfo->id);
        $this->assertNull($newCardInfo->disabled_at);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $cardNumber = rand(1000000000000000, 9999999999999999);

        //Add new Debit card
        $cardInfo = $this->user->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        //Test can see it
        $this->get('api/debit-cards/' . $cardInfo->id)
            ->assertStatus(200)
            ->assertSeeText($cardNumber);

        //Delete it
        $this->delete('api/debit-cards/' . $cardInfo->id)
            ->assertStatus(204);

        //Check if we can see it or not
        $this->get('api/debit-cards/' . $cardInfo->id)
            ->assertStatus(404);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $cardNumber = rand(1000000000000000, 9999999999999999);

        $debitCard = $this->user->debitCards()->create([
            'type' => 'Visa',
            'number' => $cardNumber,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $debitCard->debitCardTransactions()->create([
            'amount' => '99.99',
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $this->delete('api/debit-cards/' . $debitCard->id)
            ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
