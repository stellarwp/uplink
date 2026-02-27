<?php

declare(strict_types=1);

namespace StellarWP\Uplink\Legacy;

/**
 * Represents a license key discovered from a plugin's legacy storage.
 *
 * @since 3.0.0
 */
class Legacy_License
{

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $slug;


    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $brand;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $page_url;

    /**
     * @var string
     */
    public $expires_at;

    /**
     * @since 3.0.0
     */
    public function __construct(string $key, string $slug, string $name, string $brand, string $status = 'unknown', string $page_url = '')
    {
        $this->key = $key;
        $this->slug = $slug;
        $this->name = $name;
        $this->brand = $brand;
        $this->status = $status;
        $this->page_url = $page_url;
    }

    public static function fromData(array $data): self
    {
        return new static(
            $data['key'] ?? '',
            $data['slug'] ?? '',
            $data['name'] ?? '',
            $data['brand'] ?? '',
            $data['status'] ?? 'unknown',
            $data['page_url'] ?? ''
        );
    }
}
