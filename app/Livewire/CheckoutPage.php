<?php

namespace App\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Facades\Payments;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\Country;
use Illuminate\Support\Facades\Log;

class CheckoutPage extends Component
{
    /**
     * The Cart instance.
     */
    public ?Cart $cart;

    /**
     * The shipping address instance.
     */
    public ?CartAddress $shipping = null;

    /**
     * The billing address instance.
     */
    public ?CartAddress $billing = null;

    /**
     * The current checkout step.
     */
    public int $currentStep = 1;

    /**
     * Whether the shipping address is the billing address too.
     */
    public bool $shippingIsBilling = true;

    /**
     * The chosen shipping option.
     */
    public $chosenShipping = null;

    /**
     * The checkout steps.
     */
    public array $steps = [
        'shipping_address' => 1,
        'shipping_option' => 2,
        'billing_address' => 3,
        'payment' => 4,
    ];

    /**
     * The payment type we want to use.
     */
    public string $paymentType = 'cash-in-hand';

    /**
     * {@inheritDoc}
     */
    protected $listeners = [
        'cartUpdated' => 'refreshCart',
        'selectedShippingOption' => 'refreshCart',
    ];

    public $payment_intent = null;

    public $payment_intent_client_secret = null;

    protected $queryString = [
        'payment_intent',
        'payment_intent_client_secret',
    ];

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return array_merge(
            $this->getAddressValidation('shipping'),
            $this->getAddressValidation('billing'),
            [
                'shippingIsBilling' => 'boolean',
                'chosenShipping' => 'required',
            ]
        );
    }

    public function mount(): void
    {
        if (! $this->cart = CartSession::current()) {
            $this->redirect('/');

            return;
        }

        if ($this->payment_intent) {
            $payment = Payments::driver($this->paymentType)->cart($this->cart)->withData([
                'payment_intent_client_secret' => $this->payment_intent_client_secret,
                'payment_intent' => $this->payment_intent,
            ])->authorize();

            if ($payment->success) {
                redirect()->route('checkout-success.view');

                return;
            }
        }

        // Do we have a shipping address?
        $this->shipping = $this->cart->shippingAddress ?: new CartAddress;

        $this->billing = $this->cart->billingAddress ?: new CartAddress;

        // NEW: Load saved addresses for authenticated users
        $this->loadSavedAddresses();

        $this->determineCheckoutStep();
    }

    public function hydrate(): void
    {
        $this->cart = CartSession::current();
    }

    /**
     * Trigger an event to refresh addresses.
     */
    public function triggerAddressRefresh(): void
    {
        $this->dispatch('refreshAddress');
    }

    /**
     * Determines what checkout step we should be at.
     */
    public function determineCheckoutStep(): void
    {
        $shippingAddress = $this->cart->shippingAddress;
        $billingAddress = $this->cart->billingAddress;

        if ($shippingAddress) {
            if ($shippingAddress->id) {
                $this->currentStep = $this->steps['shipping_address'] + 1;
            }

            // Do we have a selected option?
            if ($this->shippingOption) {
                $this->chosenShipping = $this->shippingOption->getIdentifier();
                $this->currentStep = $this->steps['shipping_option'] + 1;
            } else {
                $this->currentStep = $this->steps['shipping_option'];
                $this->chosenShipping = $this->shippingOptions->first()?->getIdentifier();

                return;
            }
        }

        if ($billingAddress) {
            $this->currentStep = $this->steps['billing_address'] + 1;
        }
    }

    /**
     * Refresh the cart instance.
     */
    public function refreshCart(): void
    {
        $this->cart = CartSession::current();
    }

    /**
     * Return the shipping option.
     */
    public function getShippingOptionProperty()
    {
        $shippingAddress = $this->cart->shippingAddress;

        if (! $shippingAddress) {
            return;
        }

        if ($option = $shippingAddress->shipping_option) {
            return ShippingManifest::getOptions($this->cart)->first(function ($opt) use ($option) {
                return $opt->getIdentifier() == $option;
            });
        }

        return null;
    }

    /**
     * Save the address for a given type.
     */
    public function saveAddress(string $type): void
    {
        $validatedData = $this->validate(
            $this->getAddressValidation($type)
        );

        $address = $this->{$type};

        if ($type == 'billing') {
            $this->cart->setBillingAddress($address);
            $this->billing = $this->cart->billingAddress;
        }

        if ($type == 'shipping') {
            $this->cart->setShippingAddress($address);
            $this->shipping = $this->cart->shippingAddress;

            if ($this->shippingIsBilling) {
                // Do we already have a billing address?
                if ($billing = $this->cart->billingAddress) {
                    $billing->fill($validatedData['shipping']);
                    $this->cart->setBillingAddress($billing);
                } else {
                    $address = $address->only(
                        $address->getFillable()
                    );
                    $this->cart->setBillingAddress($address);
                }

                $this->billing = $this->cart->billingAddress;
            }
        }

        // NEW: Save address to authenticated user's customer record
        $this->saveAddressToUser($address, $type);

        $this->determineCheckoutStep();
    }

    /**
     * Save the address to the authenticated user's customer record
     */
    protected function saveAddressToUser($address, string $type): void
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return;
        }

        // Get the current customer from the authenticated user
        $customer = auth()->user()->latestCustomer();
        
        if (!$customer) {
            return;
        }

        // Check if this address already exists for the customer
        $existingAddress = $customer->addresses()
            ->where('first_name', $address->first_name)
            ->where('last_name', $address->last_name)
            ->where('line_one', $address->line_one)
            ->where('city', $address->city)
            ->where('postcode', $address->postcode)
            ->first();

        if ($existingAddress) {
            // Update existing address
            $existingAddress->update([
                'country_id' => $address->country_id,
                'company_name' => $address->company_name,
                'line_two' => $address->line_two,
                'line_three' => $address->line_three,
                'state' => $address->state,
                'delivery_instructions' => $address->delivery_instructions,
                'contact_email' => $address->contact_email,
                'contact_phone' => $address->contact_phone,
            ]);
        } else {
            // Create new address
            $customer->addresses()->create([
                'country_id' => $address->country_id,
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'company_name' => $address->company_name,
                'line_one' => $address->line_one,
                'line_two' => $address->line_two,
                'line_three' => $address->line_three,
                'city' => $address->city,
                'state' => $address->state,
                'postcode' => $address->postcode,
                'delivery_instructions' => $address->delivery_instructions,
                'contact_email' => $address->contact_email,
                'contact_phone' => $address->contact_phone,
                'shipping_default' => $type === 'shipping',
                'billing_default' => $type === 'billing',
            ]);
        }
    }

    /**
     * Save the selected shipping option.
     */
    public function saveShippingOption(): void
    {
        $option = $this->shippingOptions->first(fn ($option) => $option->getIdentifier() == $this->chosenShipping);

        CartSession::setShippingOption($option);

        $this->refreshCart();

        $this->determineCheckoutStep();
    }

    public function checkout()
    {
        $payment = Payments::cart($this->cart)->withData([
            'payment_intent_client_secret' => $this->payment_intent_client_secret,
            'payment_intent' => $this->payment_intent,
        ])->authorize();

        if ($payment->success) {
            redirect()->route('checkout-success.view');

            return;
        }

        return redirect()->route('checkout-success.view');
    }

    /**
     * Return the available countries.
     */
    public function getCountriesProperty(): Collection
    {
        return Country::whereIn('iso3', ['ETH'])->get();
    }

    /**
     * Return available shipping options.
     */
    public function getShippingOptionsProperty(): Collection
    {
        return ShippingManifest::getOptions(
            $this->cart
        );
    }

    /**
     * Return the address validation rules for a given type.
     */
    protected function getAddressValidation(string $type): array
    {
        return [
            "{$type}.first_name" => 'required',
            "{$type}.last_name" => 'required',
            "{$type}.line_one" => 'required',
            "{$type}.country_id" => 'required',
            "{$type}.city" => 'required',
            "{$type}.postcode" => 'required',
            "{$type}.company_name" => 'nullable',
            "{$type}.line_two" => 'nullable',
            "{$type}.line_three" => 'nullable',
            "{$type}.state" => 'nullable',
            "{$type}.delivery_instructions" => 'nullable',
            "{$type}.contact_email" => 'required|email',
            "{$type}.contact_phone" => 'nullable',
        ];
    }

    public function render(): View
    {
        Log::info("checkout-page");
        return view('livewire.checkout-page')
            ->layout('layouts.checkout');
    }

    protected function loadSavedAddresses(): void
    {
        if (!auth()->check()) {
            return;
        }

        $customer = auth()->user()->latestCustomer();
        
        if (!$customer) {
            return;
        }

        // Load default shipping address if no cart address exists
        if (!$this->shipping->id) {
            $defaultShipping = $customer->addresses()
                ->where('shipping_default', true)
                ->first();
            
            if ($defaultShipping) {
                $this->shipping = new CartAddress($defaultShipping->toArray());
            }
        }

        // Load default billing address if no cart address exists
        if (!$this->billing->id) {
            $defaultBilling = $customer->addresses()
                ->where('billing_default', true)
                ->first();
            
            if ($defaultBilling) {
                $this->billing = new CartAddress($defaultBilling->toArray());
            }
        }
    }
}
