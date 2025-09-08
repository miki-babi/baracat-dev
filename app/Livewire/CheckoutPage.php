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
use Lunar\Base\LunarUser;
use Lunar\Models\Customer;

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
        Log::info('CheckoutPage: Mount started');
        
        if (! $this->cart = CartSession::current()) {
            Log::info('CheckoutPage: No cart found, redirecting to home');
            $this->redirect('/');
            return;
        }

        Log::info('CheckoutPage: Cart found', [
            'cart_id' => $this->cart->id,
            'user_id' => $this->cart->user_id,
            'payment_intent' => $this->payment_intent,
            'payment_intent_client_secret' => $this->payment_intent_client_secret
        ]);

        if ($this->payment_intent) {
            Log::info('CheckoutPage: Processing payment intent', [
                'payment_intent' => $this->payment_intent,
                'payment_intent_client_secret' => $this->payment_intent_client_secret
            ]);
            
            $payment = Payments::driver($this->paymentType)->cart($this->cart)->withData([
                'payment_intent_client_secret' => $this->payment_intent_client_secret,
                'payment_intent' => $this->payment_intent,
            ])->authorize();

            Log::info('CheckoutPage: Payment authorization result', [
                'success' => $payment->success,
                'message' => $payment->message ?? 'No message',
                'status' => $payment->status ?? 'No status'
            ]);

            if ($payment->success) {
                Log::info('CheckoutPage: Payment successful, redirecting to success page');
                // return redirect()->route('checkout-success.view');
                return;
            }
        }

        // Do we have a shipping address?
        $this->shipping = $this->cart->shippingAddress ?: new CartAddress;
        $this->billing = $this->cart->billingAddress ?: new CartAddress;

        Log::info('CheckoutPage: Addresses loaded', [
            'has_shipping' => $this->shipping->id ? true : false,
            'has_billing' => $this->billing->id ? true : false,
            'shipping_address' => $this->shipping->id ? $this->shipping->toArray() : 'new address',
            'billing_address' => $this->billing->id ? $this->billing->toArray() : 'new address'
        ]);

        // NEW: Load saved addresses for authenticated users
        $this->loadSavedAddresses();

        $this->determineCheckoutStep();
        
        Log::info('CheckoutPage: Mount completed', [
            'current_step' => $this->currentStep,
            'shipping_is_billing' => $this->shippingIsBilling
        ]);
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
        // Normalize to array
        $addr = is_array($address) ? $address : (method_exists($address, 'toArray') ? $address->toArray() : (array) $address);

        Log::info('CheckoutPage: Saving address to user', [
            'type' => $type,
            'address' => $addr,
        ]);
        
        // Get the current cart
        $cart = CartSession::current();
        
        // Check if cart has an associated user
        if (!$cart || !$cart->user_id) {
            Log::info('CheckoutPage: No cart or user for saving address');
            return;
        }

        // Get the user from the cart
        $user = $cart->user;
        if (!$user) {
            Log::info('CheckoutPage: User not found for saving address');
            return;
        }

        // Get the current customer from the authenticated user
        $customer = Customer::where('email', $user->email)->first();
        Log::info('saveAddressToUser: Customer found'. $customer);
        
        if (!$customer) {
            Log::info('CheckoutPage: No customer found for saving address');
            return;
        }

        Log::info('CheckoutPage: Saving address to customer', [
            'customer_id' => $customer->id,
            'type' => $type
        ]);

        // Check if this address already exists for the customer
        $existingAddress = $customer->addresses()
            ->where('first_name', $addr['first_name'] ?? null)
            ->where('last_name', $addr['last_name'] ?? null)
            ->where('line_one', $addr['line_one'] ?? null)
            ->where('city', $addr['city'] ?? null)
            ->where('postcode', $addr['postcode'] ?? null)
            ->first();

        if ($existingAddress) {
            Log::info('CheckoutPage: Updating existing address', ['address_id' => $existingAddress->id]);
            // Update existing address
            $existingAddress->update([
                'country_id' => $addr['country_id'] ?? null,
                'company_name' => $addr['company_name'] ?? null,
                'line_two' => $addr['line_two'] ?? null,
                'line_three' => $addr['line_three'] ?? null,
                'state' => $addr['state'] ?? null,
                'delivery_instructions' => $addr['delivery_instructions'] ?? null,
                'contact_email' => $addr['contact_email'] ?? null,
                'contact_phone' => $addr['contact_phone'] ?? null,
            ]);
        } else {
            Log::info('CheckoutPage: Creating new address');
            // Create new address
            $newAddress = $customer->addresses()->create([
                'country_id' => $addr['country_id'] ?? null,
                'first_name' => $addr['first_name'] ?? '',
                'last_name' => $addr['last_name'] ?? '',
                'company_name' => $addr['company_name'] ?? null,
                'line_one' => $addr['line_one'] ?? '',
                'line_two' => $addr['line_two'] ?? null,
                'line_three' => $addr['line_three'] ?? null,
                'city' => $addr['city'] ?? '',
                'state' => $addr['state'] ?? null,
                'postcode' => $addr['postcode'] ?? null,
                'delivery_instructions' => $addr['delivery_instructions'] ?? null,
                'contact_email' => $addr['contact_email'] ?? null,
                'contact_phone' => $addr['contact_phone'] ?? null,
                'shipping_default' => $type === 'shipping',
                'billing_default' => $type === 'billing',
            ]);
            Log::info('CheckoutPage: New address created', ['address_id' => $newAddress->id]);
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
        Log::info('CheckoutPage: Checkout method called', [
            'cart_id' => $this->cart->id,
            'payment_intent' => $this->payment_intent,
            'payment_intent_client_secret' => $this->payment_intent_client_secret,
            'current_step' => $this->currentStep
        ]);

        $payment = Payments::cart($this->cart)->withData([
            'payment_intent_client_secret' => $this->payment_intent_client_secret,
            'payment_intent' => $this->payment_intent,
        ])->authorize();

        Log::info('CheckoutPage: Payment authorization completed', [
            'success' => $payment->success,
            'message' => $payment->message ?? 'No message',
            'status' => $payment->status ?? 'No status',
            'payment_id' => $payment->id ?? 'No payment ID',
            'cart_id' => $this->cart->id,
            'user_id' => $this->cart->user_id,
            'customer_id' => $this->cart->customer_id,
        ]);
        // $customer = Customer::where('user_id',$this->cart->user_id )->first();
        Log::info('CheckoutPage: Payment customer', [
            'customer' => Customer::where('id',$this->cart->user_id )->first(),
        ]);

        CartSession::setCustomer(Customer::where('id',$this->cart->user_id )->first());
        Log::info(CartSession::getCustomer());
        

        if ($payment->success) {
            Log::info('CheckoutPage: Payment successful, attempting redirect to success page');
            return redirect()->route('checkout-success.view');
            // return;
        }

        Log::info('CheckoutPage: Payment failed, but still redirecting to success page');
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
        return view('livewire.checkout-page', [
            'layout' => 'layouts.checkout'
        ]);
    }

    protected function loadSavedAddresses(): void
    {
        Log::info('CheckoutPage: Loading saved addresses');
        
        // Get the current cart
        $cart = CartSession::current();
        
        // Check if cart has an associated user
        if (!$cart || !$cart->user_id) {
            Log::info('CheckoutPage: No cart or user associated, skipping address loading');
            return;
        }

        Log::info('CheckoutPage: Cart has user', ['user_id' => $cart->user_id]);

        // Get the user from the cart
        $user = $cart->user;
        
        if (!$user) {
            Log::info('CheckoutPage: User not found for cart');
            return;
        }

        Log::info('CheckoutPage: User found', ['user_id' => $user->id, 'user_email' => $user->email]);

        // $customer = $user->latestCustomer();
        // $customer = LunarUser::where('email', $user->email)->first(); // null if not found
        
        $customer = Customer::where('email', $user->email)->first();
        // $userCart = Cart::where('user_id', $user->id)->first();

        // $customer = $userCart->customer;

        if (!$customer) {
            Log::info('CheckoutPage: No customer found for user');
            return;
        }

        Log::info('CheckoutPage: Customer found'. $customer);
        // Log::info('CheckoutPage: Customer found', ['customer_id' => $customer->customer->id]);

        // Load default shipping address if no cart address exists
        if (!$this->shipping->id) {
            Log::info('CheckoutPage: No shipping address in cart, looking for saved shipping address');
            
            $defaultShipping = $customer->addresses()
                ->where('shipping_default', true)
                ->first();
            
            if ($defaultShipping) {
                Log::info('CheckoutPage: Found saved shipping address, auto-filling', [
                    'address_id' => $defaultShipping->id,
                    'address' => $defaultShipping->toArray()
                ]);
                $this->shipping = new CartAddress($defaultShipping->toArray());
            } else {
                Log::info('CheckoutPage: No saved shipping address found');
            }
        } else {
            Log::info('CheckoutPage: Cart already has shipping address, not auto-filling');
        }

        // Load default billing address if no cart address exists
        if (!$this->billing->id) {
            Log::info('CheckoutPage: No billing address in cart, looking for saved billing address');
            
            $defaultBilling = $customer->addresses()
                ->where('billing_default', true)
                ->first();
            
            if ($defaultBilling) {
                Log::info('CheckoutPage: Found saved billing address, auto-filling', [
                    'address_id' => $defaultBilling->id,
                    'address' => $defaultBilling->toArray()
                ]);
                $this->billing = new CartAddress($defaultBilling->toArray());
            } else {
                Log::info('CheckoutPage: No saved billing address found');
            }
        } else {
            Log::info('CheckoutPage: Cart already has billing address, not auto-filling');
        }
    }
}
