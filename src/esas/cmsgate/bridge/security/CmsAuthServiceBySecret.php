<?php
namespace esas\cmsgate\bridge\security;

use esas\cmsgate\bridge\BridgeConnector;
use esas\cmsgate\bridge\dao\ShopConfigBridgeRepository;
use esas\cmsgate\utils\CMSGateException;

abstract class CmsAuthServiceBySecret extends CmsAuthService
{
    private $requestFieldLogin;
    private $requestFieldSignature;

    /**
     * ConfigCacheService constructor.
     * @param $requestFieldLogin
     * @param $requestFieldSignature
     */
    public function __construct($requestFieldLogin, $requestFieldSignature)
    {
        parent::__construct();
        $this->requestFieldLogin = $requestFieldLogin;
        $this->requestFieldSignature = $requestFieldSignature;
    }

    public function checkAuth(&$request)
    {
        $login = $request[$this->requestFieldLogin];

        /** @var ShopConfigBridgeRepository $shopConfigRepository */
        $shopConfigRepository = BridgeConnector::fromRegistry()->getShopConfigRepository();
        $secret = $shopConfigRepository->getSecretByLogin($login);
        $signature = $this->generateVerificationSignature($request, $secret);
        if ($signature !== $request[$this->requestFieldSignature])
            throw new CMSGateException('Signature is incorrect');
        $configCache = BridgeConnector::fromRegistry()->getShopConfigRepository()->getByLogin($login);
        return $configCache;
    }


    public abstract function generateVerificationSignature($request, $secret);
}