<?php

namespace Flashy\Integration\Service;

class IsOrderPlaceService
{
    private $isorderPlaced = false;

    public function setIsOrderPlaced($isorderPlaced)
    {
        $this->isorderPlaced = $isorderPlaced;
    }

    public function getIsOrderPlaced()
    {
        return $this->isorderPlaced;
    }
}
