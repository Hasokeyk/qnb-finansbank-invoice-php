<?php

namespace QnbSolutions\QnbEsolutions\builder;

/**
 * Faturadaki ürün/hizmet satırları.
 *
 * İlk satır zincirleme doldurulur; `add_product()` ile ek satır eklenir:
 *
 *     (new products)
 *         ->set_product_name('Kalem')->set_quantity(2)->set_unit_price(10)->set_vat_rate(20)
 *         ->add_product()
 *         ->set_product_name('Defter')->set_quantity(1)->set_unit_price(50)->set_vat_rate(20)
 */
class products
{
    /** @var array<int, array<string, mixed>> */
    private array $lines = [];

    private function empty_line(): array
    {
        return [
            'name' => '',
            'quantity' => 0,
            'unit' => 'C62',
            'unit_price' => 0.0,
            'vat_rate' => 0.0,
            'discount' => 0.0,
            'vat_exemption_code' => '',
            'vat_exemption_description' => '',
        ];
    }

    /** Yeni boş satır ekler; sonraki setter'lar ona yönlenir. */
    public function add_product(): static
    {
        $this->lines[] = $this->empty_line();
        return $this;
    }

    public function set_product_name(string $name): static
    {
        $this->last_line()['name'] = $name;
        return $this;
    }

    public function set_quantity(int|float $quantity): static
    {
        $this->last_line()['quantity'] = $quantity;
        return $this;
    }

    public function set_unit(string $unit): static
    {
        $this->last_line()['unit'] = $unit;
        return $this;
    }

    public function set_unit_price(float $unit_price): static
    {
        $this->last_line()['unit_price'] = $unit_price;
        return $this;
    }

    public function set_vat_rate(float $vat_rate): static
    {
        $this->last_line()['vat_rate'] = $vat_rate;
        return $this;
    }

    public function set_discount(float $discount): static
    {
        $this->last_line()['discount'] = $discount;
        return $this;
    }

    public function set_vat_exemption_code(string $code): static
    {
        $this->last_line()['vat_exemption_code'] = $code;
        return $this;
    }

    public function set_vat_exemption_description(string $description): static
    {
        $this->last_line()['vat_exemption_description'] = $description;
        return $this;
    }

    /** Build sırasında kullanılacak satır listesi. */
    public function lines(): array
    {
        if ($this->lines === []) {
            $this->lines[] = $this->empty_line();
        }
        return $this->lines;
    }

    private function &last_line(): array
    {
        if ($this->lines === []) {
            $this->lines[] = $this->empty_line();
        }
        return $this->lines[array_key_last($this->lines)];
    }
}
