<?php

namespace QnbSolutions\QnbEsolutions\builder;

/**
 * Customer (fatura kesilecek firma) bilgisi.
 */
class customer_company
{
    private string $company_name = '';
    private string $tax_number = '';
    private string $first_name = '';
    private string $last_name = '';
    private string $tax_office = 'ÇANKAYA';
    private string $address = '';
    private string $district = '';
    private string $city = '';
    private string $country = 'Türkiye';

    public function set_company_name(string $company_name): static
    {
        $this->company_name = $company_name;
        return $this;
    }

    public function set_tax_number(string $tax_number): static
    {
        $this->tax_number = $tax_number;
        return $this;
    }

    public function set_first_name(string $first_name): static
    {
        $this->first_name = $first_name;
        return $this;
    }

    public function set_last_name(string $last_name): static
    {
        $this->last_name = $last_name;
        return $this;
    }

    public function set_tax_office(string $tax_office): static
    {
        $this->tax_office = $tax_office;
        return $this;
    }

    public function set_address(string $address, string $district, string $city, string $country = 'Türkiye'): static
    {
        $this->address = $address;
        $this->district = $district;
        $this->city = $city;
        $this->country = $country;
        return $this;
    }

    public function company_name(): string
    {
        return $this->company_name;
    }

    public function tax_number(): string
    {
        return $this->tax_number;
    }

    public function first_name(): string
    {
        return $this->first_name;
    }

    public function last_name(): string
    {
        return $this->last_name;
    }

    public function tax_office(): string
    {
        return $this->tax_office;
    }

    public function address(): string
    {
        return $this->address;
    }

    public function district(): string
    {
        return $this->district;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function country(): string
    {
        return $this->country;
    }
}
