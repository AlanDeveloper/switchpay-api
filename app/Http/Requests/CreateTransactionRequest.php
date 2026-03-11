<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;
use Illuminate\Validation\Validator;

class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "client_id" => "required|integer",
            "products" => "required|array",
            "products.*.id" => "required|integer",
            "products.*.quantity" => "required|integer",
            "card_number" => "required|string",
            "cvv" => "required|max:3",
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->any()) {
                    return;
                }

                $productsPayload = $this->input("products");
                $productIds = collect($productsPayload)->pluck("id");

                $dbProducts = Product::whereIn("id", $productIds)
                    ->get()
                    ->keyBy("id");

                foreach ($productsPayload as $item) {
                    $product = $dbProducts->get($item["id"]);

                    if (
                        $product &&
                        $product->available_amount < $item["quantity"]
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "products.{$item["id"]}",
                                "Product '{$product->name}' only has {$product->available_amount} units available.",
                            );
                    }
                }
            },
        ];
    }
}
