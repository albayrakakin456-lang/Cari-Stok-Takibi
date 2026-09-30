<?php

namespace App\Http\Requests;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class CampaignRequest extends FormRequest//alt sınıflar kullanacak,kendisi kullanılmayacak.
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $prepared = [
            'code' => filled($this->input('code'))
                ? mb_strtoupper(trim((string) $this->input('code')))
                : null,
            // Öncelik panel kullanıcısının vereceği bir karar değildir.
            // Motor önce kampanya aşamasına, aynı aşamada ise kayıt sırasına bakar.
            'priority' => 100,
            'is_active' => $this->boolean('is_active'),
            'is_exclusive' => $this->boolean('is_exclusive'),
        ];

        if ($this->input('type') === CampaignType::Conditional->value) {
            $parameters = (array) $this->input('parameters', []);
            $parameters['repeat_reward'] = $this->boolean('parameters.repeat_reward');
            $prepared['parameters'] = $parameters;
        }

        $this->merge($prepared);
    }

    public function rules(): array
    {
        $type = $this->campaignType();
        $targetType = $type?->targetType();
        $campaign = $this->route('campaign');
        $isConditional = $type === CampaignType::Conditional;

        $codeRule = Rule::unique('campaigns', 'code')
            ->where(fn ($query) => $query->where('user_id', auth()->id()));

        if ($campaign instanceof Campaign) {
            $codeRule->ignore($campaign);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'alpha_dash:ascii', 'max:100', $codeRule],
            'type' => ['required', Rule::enum(CampaignType::class)],//yalnızcs enum değerleri kabul etsin.
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'integer', 'between:0,10000'],
            'is_active' => ['required', 'boolean'],
            'is_exclusive' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            ...($isConditional ? [] : [
                'target_ids' => ['required', 'array', 'min:1'],
                'target_ids.*' => [
                    'required',
                    'integer',
                    'distinct',
                    $this->targetExistsRule($targetType),
                ],
            ]),
            ...$this->parameterRules($type),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Kampanya adını girin.',
            'type.required' => 'Kampanya türünü seçin.',
            'code.alpha_dash' => 'Kampanya kodunda yalnızca harf, rakam, tire ve alt çizgi kullanılabilir.',
            'code.unique' => 'Bu kampanya kodu daha önce kullanılmış.',
            'type.enum' => 'Geçerli bir kampanya türü seçiniz.',
            'ends_at.after_or_equal' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
            'target_ids.required' => 'En az bir ürün veya kategori seçmelisiniz.',
            'target_ids.*.exists' => 'Seçilen hedef bulunamadı veya size ait değil.',
            'parameters.array' => 'Bu kampanya türüne ait olmayan bir ayar gönderildi.',
            'parameters.discount_rate.max' => 'İndirim oranı %100 değerini aşamaz.',
            'parameters.pay_quantity.lt' => 'Ödenecek miktar, alınacak miktardan küçük olmalıdır.',
            'parameters.discount_amount.lte' => 'İndirim tutarı minimum kategori tutarını aşamaz.',
            'parameters.conditions.*.source_type.required' => 'Her kural için koşul türünü seçin.',
            'parameters.conditions.*.target_id.required' => 'Her kural için bir ürün veya kategori seçin.',
            'parameters.conditions.*.minimum_quantity.integer' => 'Minimum adet tam sayı olmalıdır.',
            'parameters.conditions.*.minimum_quantity.min' => 'Minimum adet en az 1 olmalıdır.',
            'parameters.conditions.*.minimum_amount.numeric' => 'Minimum tutar geçerli bir sayı olmalıdır.',
            'parameters.conditions.*.minimum_amount.gt' => 'Minimum tutar 0 TL’den büyük olmalıdır.',
            'parameters.reward_type.required' => 'İndirim türünü seçin.',
            'parameters.reward_value.required' => 'İndirim değerini girin.',
            'parameters.reward_value.numeric' => 'İndirim değeri geçerli bir sayı olmalıdır.',
            'parameters.reward_value.gt' => 'İndirim değeri 0’dan büyük olmalıdır.',
            'parameters.reward_scope.required' => 'İndirimin hangi ürünlere uygulanacağını seçin.',
            'parameters.reward_target_ids.required' => 'İndirimin uygulanacağı en az bir ürün veya kategori seçin.',
            'parameters.reward_target_ids.min' => 'İndirimin uygulanacağı en az bir ürün veya kategori seçin.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->campaignType() !== CampaignType::Conditional) {
                return;
            }

            foreach ((array) $this->input('parameters.conditions', []) as $index => $condition) {
                $quantityMissing = blank($condition['minimum_quantity'] ?? null);
                $amountMissing = blank($condition['minimum_amount'] ?? null);

                // İki alan için ayrı hata üretmek yerine kullanıcıya tek açıklama gösterilir.
                if ($quantityMissing && $amountMissing) {
                    $validator->errors()->add(
                        "parameters.conditions.{$index}.minimum_quantity",
                        ($index + 1).'. kural için minimum adet veya minimum tutardan birini girin.',
                    );
                }
            }
        }];
    }

    private function campaignType(): ?CampaignType
    {
        return CampaignType::tryFrom((string) $this->input('type'));
    }

    private function targetExistsRule(?CampaignTargetType $targetType): object
    {
        $table = match ($targetType) {
            CampaignTargetType::Category => 'categories',
            default => 'products',
        };

        return Rule::exists($table, 'id')
            ->where(fn ($query) => $query->where('user_id', auth()->id()));
    }

    private function parameterRules(?CampaignType $type): array
    {
        return match ($type) {
            CampaignType::ProductPercentage,
            CampaignType::CategoryPercentage => [
                'parameters' => ['required', 'array:discount_rate'],
                'parameters.discount_rate' => ['required', 'numeric', 'gt:0', 'max:100'],
            ],
            CampaignType::ProductFixed => [
                'parameters' => ['required', 'array:discount_amount'],
                'parameters.discount_amount' => ['required', 'numeric', 'gt:0'],
            ],
            CampaignType::BuyXPayY => [
                'parameters' => ['required', 'array:buy_quantity,pay_quantity'],
                'parameters.buy_quantity' => ['required', 'integer', 'min:2'],
                'parameters.pay_quantity' => ['required', 'integer', 'min:1', 'lt:parameters.buy_quantity'],
            ],
            CampaignType::CategoryThirdHalf => [
                'parameters' => ['required', 'array:different_product_count,discount_rate'],
                'parameters.different_product_count' => ['required', 'integer', 'min:2'],
                'parameters.discount_rate' => ['required', 'numeric', 'gt:0', 'max:100'],
            ],
            CampaignType::CategoryThresholdFixed => [
                'parameters' => ['required', 'array:minimum_amount,discount_amount'],
                'parameters.minimum_amount' => ['required', 'numeric', 'gt:0'],
                'parameters.discount_amount' => ['required', 'numeric', 'gt:0', 'lte:parameters.minimum_amount'],
            ],
            CampaignType::Conditional => [
                'parameters' => ['required', 'array:conditions,reward_type,reward_value,reward_scope,reward_target_ids,repeat_reward'],
                'parameters.conditions' => ['required', 'array', 'min:1', 'max:10'],
                'parameters.conditions.*' => ['required', 'array:source_type,target_id,minimum_quantity,minimum_amount'],
                'parameters.conditions.*.source_type' => ['required', Rule::in(['product', 'category'])],
                'parameters.conditions.*.target_id' => [
                    'required',
                    'integer',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $index = explode('.', $attribute)[2] ?? null;
                        $sourceType = $this->input("parameters.conditions.{$index}.source_type");
                        if (! $this->ownedTargetExists($sourceType, (int) $value)) {
                            $fail('Seçilen koşul hedefi bulunamadı veya size ait değil.');
                        }
                    },
                ],
                'parameters.conditions.*.minimum_quantity' => [
                    'nullable', 'integer', 'min:1',
                ],
                'parameters.conditions.*.minimum_amount' => [
                    'nullable', 'numeric', 'gt:0',
                ],
                'parameters.reward_type' => ['required', Rule::in(['percentage', 'fixed'])],
                'parameters.reward_value' => [
                    'required',
                    'numeric',
                    'gt:0',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if ($this->input('parameters.reward_type') === 'percentage' && (float) $value > 100) {
                            $fail('Yüzde indirim değeri 100 değerini aşamaz.');
                        }
                    },
                ],
                'parameters.reward_scope' => [
                    'required',
                    Rule::in(['matching_items', 'selected_products', 'selected_categories']),
                ],
                'parameters.repeat_reward' => ['required', 'boolean'],
                'parameters.reward_target_ids' => [
                    Rule::excludeIf(fn () => $this->input('parameters.reward_scope') === 'matching_items'),
                    'nullable',
                    'array',
                    'min:1',
                    Rule::requiredIf(fn () => in_array(
                        $this->input('parameters.reward_scope'),
                        ['selected_products', 'selected_categories'],
                        true,
                    )),
                ],
                'parameters.reward_target_ids.*' => [
                    'integer',
                    'distinct',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $sourceType = $this->input('parameters.reward_scope') === 'selected_categories'
                            ? 'category'
                            : 'product';
                        if (! $this->ownedTargetExists($sourceType, (int) $value)) {
                            $fail('Seçilen ödül hedefi bulunamadı veya size ait değil.');
                        }
                    },
                ],
            ],
            default => [
                'parameters' => ['required', 'array'],
            ],
        };
    }

    private function ownedTargetExists(mixed $sourceType, int $targetId): bool
    {
        $model = $sourceType === 'category' ? Category::class : Product::class;

        return $model::query()
            ->whereKey($targetId)
            ->where('user_id', auth()->id())
            ->exists();
    }
}
