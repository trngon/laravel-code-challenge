<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        $this->debitCard->debitCardTransactions()->create([
            'amount' => $amount = rand(1, 1000),
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $this->get('api/debit-card-transactions?debit_card_id=' . $this->debitCard->id)
            ->assertStatus(200)
            ->assertSeeText($amount);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $anotherUser = User::factory()->create();
        $anotherDebitCard = DebitCard::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $anotherDebitCard->debitCardTransactions()->create([
            'amount' => $amount = rand(1, 1000),
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $this->get('api/debit-card-transactions?debit_card_id=' . $anotherDebitCard->id)
            ->assertStatus(403);

    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $this->post('api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount = rand(1, 1000),
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ])
            ->assertStatus(201)
            ->assertSeeText($amount);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $anotherUser = User::factory()->create();
        $anotherDebitCard = DebitCard::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $this->post('api/debit-card-transactions', [
            'debit_card_id' => $anotherDebitCard->id,
            'amount' => $amount = rand(1, 1000),
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $anotherDebitCard->id,
            'amount' => $amount,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = $this->debitCard->debitCardTransactions()->create([
            'amount' => $amount = rand(1, 1000),
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $this->get('api/debit-card-transactions/' . $debitCardTransaction->id . '/?debit_card_id=' . $this->debitCard->id)
            ->assertStatus(200)
            ->assertJsonFragment([
                'amount' => (string)$amount
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $anotherUser = User::factory()->create();
        $anotherDebitCard = DebitCard::factory()->create([
            'user_id' => $anotherUser->id
        ]);
        $anotherDebitCardTransaction = $anotherDebitCard->debitCardTransactions()->create([
            'amount' => $amount = rand(1, 1000),
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $this->get('api/debit-card-transactions/' . $anotherDebitCardTransaction->id . '/?debit_card_id=' . $this->debitCard->id)
            ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
