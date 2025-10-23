<?php

namespace App\Services;

use Aws\CloudFront\CloudFrontClient;
use Carbon\Carbon;

class CloudFrontService
{
    protected $client;
    protected $keyPairId;
    protected $privateKey;

    public function __construct()
    {
        $this->client = new CloudFrontClient([
            'region' => config('filesystems.disks.s3.region'),
            'version' => 'latest',
        ]);

        $this->keyPairId = config('services.cloudfront.key_pair_id');
        $this->privateKey = file_get_contents(config('services.cloudfront.private_key_path'));
    }

    public function getSignedUrl(string $path, int $expiresInMinutes = 15): string
    {
        $resourceKey = rtrim(config('services.cloudfront.url'), '/') . '/' . ltrim($path, '/');
        $expires = Carbon::now()->addMinutes($expiresInMinutes)->timestamp;

        return $this->client->getSignedUrl([
            'url' => $resourceKey,
            'expires' => $expires,
            'private_key' => $this->privateKey,
            'key_pair_id' => $this->keyPairId,
        ]);
    }
}
