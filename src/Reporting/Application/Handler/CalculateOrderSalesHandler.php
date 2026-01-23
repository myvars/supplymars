<?php

namespace App\Reporting\Application\Handler;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Reporting\Domain\Model\SalesType\OrderSales;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateOrderSalesHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ) {
    }

    public function process(string $date): void
    {
        $sales = $this->getOrderSales($date);

        $this->removeExistingOrderSales($date);

        foreach ($sales as $sale) {
            $orderSales = OrderSales::create(
                $date,
                $sale['orderCount'],
                $sale['orderValue'],
                $sale['averageOrderValue']
            );

            $errors = $this->validator->validate($orderSales);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

            $this->em->persist($orderSales);
        }

        $this->em->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getOrderSales(string $date): array
    {
        return $this->em->getRepository(CustomerOrder::class)
            ->findOrderSalesByDate(new \DateTime($date), new \DateTime($date)->modify('+ 1 day'));
    }

    private function removeExistingOrderSales(string $date): void
    {
        $this->em->getRepository(OrderSales::class)
            ->deleteByDate($date);
    }
}
