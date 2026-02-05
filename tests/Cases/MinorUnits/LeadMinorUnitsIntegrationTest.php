<?php

declare(strict_types=1);

namespace Cases\MinorUnits;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Client\LongLivedAccessToken;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Models\LeadModel;
use PHPUnit\Framework\TestCase;

class LeadMinorUnitsIntegrationTest extends TestCase
{
    /** @var AmoCRMApiClient */
    private $apiClient;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 3);
        if (file_exists($root . '/.env')) {
            $dotenv = new \Symfony\Component\Dotenv\Dotenv();
            $dotenv->usePutenv(true);
            $dotenv->load($root . '/.env');
        }

        $accessToken = new LongLivedAccessToken((string)getenv('ACCESS_TOKEN'));

        $this->apiClient = new AmoCRMApiClient(
            (string)getenv('CLIENT_ID'),
            (string)getenv('CLIENT_SECRET'),
            (string)getenv('REDIRECT_URI')
        );

        $this->apiClient->setAccessToken($accessToken)
            ->setAccountBaseDomain('kzhiloldinov.zhiloldinov.kommo2.com');

        $this->apiClient->getOAuthClient()->setProtocol('http://');
    }

    public function testAddOneWithMinorUnits()
    {
        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Integration Test Lead ' . uniqid())
            ->setPriceWithMinorUnits(150.75);

        $createdLead = $leadsService->addOne($lead);

        $this->assertNotNull($createdLead->getId());
        $this->assertEquals(150.75, $createdLead->getPriceWithMinorUnits());
        $this->assertEquals(150, $createdLead->getPrice());

        return $createdLead->getId();
    }

    /**
     * @depends testAddOneWithMinorUnits
     */
    public function testGetOneWithMinorUnits(int $leadId)
    {
        $leadsService = $this->apiClient->leads();
        $lead = $leadsService->getOne($leadId);

        $this->assertEquals(150.75, $lead->getPriceWithMinorUnits());
        $this->assertEquals(150, $lead->getPrice());
    }

    /**
     * @depends testAddOneWithMinorUnits
     */
    public function testUpdateOneWithMinorUnits(int $leadId)
    {
        $leadsService = $this->apiClient->leads();
        $lead = $leadsService->getOne($leadId);
        $lead->setPriceWithMinorUnits(200.99);

        $updatedLead = $leadsService->updateOne($lead);

        $this->assertEquals(200.99, $updatedLead->getPriceWithMinorUnits());
        $this->assertEquals(200, $updatedLead->getPrice());

        $fetchedLead = $leadsService->getOne($leadId);
        $this->assertEquals(200.99, $fetchedLead->getPriceWithMinorUnits());
    }

    public function testAddComplexWithMinorUnits()
    {
        $leadsService = $this->apiClient->leads();
        $collection = new LeadsCollection();

        $lead1 = new LeadModel();
        $lead1->setName('Complex Lead 1 ' . uniqid())
            ->setPriceWithMinorUnits(10.11);

        $lead2 = new LeadModel();
        $lead2->setName('Complex Lead 2 ' . uniqid())
            ->setPriceWithMinorUnits(20.22);

        $collection->add($lead1)->add($lead2);

        $addedCollection = $leadsService->addComplex($collection);

        $this->assertCount(2, $addedCollection);

        foreach ($addedCollection as $lead) {
            $fetched = $leadsService->getOne($lead->getId());
            $price = $fetched->getPriceWithMinorUnits();
            $this->assertTrue($price === 10.11 || $price === 20.22);
        }
    }

    public function testUpdateComplexWithMinorUnits()
    {
        $leadsService = $this->apiClient->leads();
        $lead1 = new LeadModel();
        $lead1->setName('To Update 1 ' . uniqid())->setPriceWithMinorUnits(55.55);
        $lead1 = $leadsService->addOne($lead1);

        $lead2 = new LeadModel();
        $lead2->setName('To Update 2 ' . uniqid())->setPriceWithMinorUnits(66.66);
        $lead2 = $leadsService->addOne($lead2);

        $collection = new LeadsCollection();

        $lead1->setPriceWithMinorUnits(55.99);
        $lead2->setPriceWithMinorUnits(66.99);

        $collection->add($lead1)->add($lead2);

        $updatedCollection = $leadsService->update($collection);

        $fetched1 = $leadsService->getOne($lead1->getId());
        $this->assertEquals(55.99, $fetched1->getPriceWithMinorUnits());

        $fetched2 = $leadsService->getOne($lead2->getId());
        $this->assertEquals(66.99, $fetched2->getPriceWithMinorUnits());
    }
}
