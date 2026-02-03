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
        $accessToken = new LongLivedAccessToken('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjMxNDNmODVhMjI5YmJjZThjNWNiOTM0OGEzODdlYmMzYmMwZWM1M2E0YzdhZjhmNDVlZjE4NjYyOGYzZDM3ZDA2NTkzODNhZmIyMTcyMDZkIn0.eyJhdWQiOiI1ZDgyMDg4OS05M2ExLTRhYWUtODBhZC1kNmY3MGUwZWM2YTMiLCJqdGkiOiIzMTQzZjg1YTIyOWJiY2U4YzVjYjkzNDhhMzg3ZWJjM2JjMGVjNTNhNGM3YWY4ZjQ1ZWYxODY2MjhmM2QzN2QwNjU5MzgzYWZiMjE3MjA2ZCIsImlhdCI6MTc3MDA0MjA2MiwibmJmIjoxNzcwMDQyMDYyLCJleHAiOjE3Nzk0OTQ0MDAsInN1YiI6Ijg0NjE3MDciLCJncmFudF90eXBlIjoiIiwiYWNjb3VudF9pZCI6Mjk4Mzc0NDEsImJhc2VfZG9tYWluIjoia29tbW8uY29tIiwidmVyc2lvbiI6Miwic2NvcGVzIjpbInB1c2hfbm90aWZpY2F0aW9ucyIsImNybSIsIm5vdGlmaWNhdGlvbnMiLCJmaWxlcyIsImZpbGVzX2RlbGV0ZSJdLCJoYXNoX3V1aWQiOiJlZjE2NmU2ZS1jNjE0LTQxNWYtYmEyMS0xMjNkMDM2NjE3NGYiLCJhcGlfZG9tYWluIjoiYXBpLm1haW42LmtvbW1vMi5jb20ifQ.WVwXq4CV-bRr1kajDYuqiRjJLTJ1bdZvf9TqTJRN2SWRPsaGVZtL4DX8XasjOmQxWwAceIDDbVOZwQqOKKdhHdAeE7Y7p5fzv_wG38VzrVSo-u_e_3uTargJQ-gJzZDh_sMPnz9JOfjaqBTwxDzAyoRLAjEhNNKEebt-7rW6xTvh0VODJFuKIaeGA6koI6z1qyTXhmhD_i37GwG485v7DUFCQmcRvUnOENfya2H1aSIjwnBjdAS9Z9fdw00b3-hVizAPtU_O8M07rdLSF41aY55BnZ_d5D0PY5pZSMIYOBAi7ntHClvy12Tz245zs1O225qeYMWLqHIhDkpvhyJNLQ');

        $this->apiClient = new AmoCRMApiClient(
            '5d820889-93a1-4aae-80ad-d6f70e0ec6a3',
            'G0mUlE17sGcWYRiRoVP5tihNatfFRdIX71TAWUMgUSTAvc7NwFI4d77fucQEpdg3',
            'https://refined-grub-awfully.ngrok-free.app'
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
