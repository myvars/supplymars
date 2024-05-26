<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class PurchaseOrderHelper
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function createPurchaseOrder()
    {

    }

    public function shipPurchaseOrder()
    {

    }

    public function deliverPurchaseOrder()
    {

    }

    public function removePurchaseOrder()
    {

    }

    public function addPurchaseOrderItem()
    {

    }

    public function updatePurchaseOrderItem()
    {

    }

    public function removePurchaseOrderItem()
    {

    }
}