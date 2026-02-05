<?php

declare(strict_types=1);

namespace Cases\MinorUnits;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Client\LongLivedAccessToken;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Models\LeadModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Models\TagModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CompanyModel;

class LeadMinorUnitsIntegrationTest extends TestCase
{
    /** @var AmoCRMApiClient */
    private $apiClient;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 3);
        if (file_exists($root . '/.env')) {
            $dotenv = new Dotenv();
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

    public function testAddOneWithZeroPrice()
    {
        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Zero Price Lead ' . uniqid())
            ->setPriceWithMinorUnits(0.0);

        $createdLead = $leadsService->addOne($lead);

        $this->assertEquals(0.0, $createdLead->getPriceWithMinorUnits());
        $this->assertEquals(0, $createdLead->getPrice());
    }

    public function testAddOneWithNegativePrice()
    {
        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Negative Price Lead ' . uniqid())
            ->setPriceWithMinorUnits(-10.50);

        $this->expectException(\AmoCRM\Exceptions\AmoCRMApiErrorResponseException::class);
        $leadsService->addOne($lead);
    }

    public function testAddOneWithHighPrecision()
    {
        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $inputPrice = 10.1234;

        $lead->setName('High Precision Lead ' . uniqid())
            ->setPriceWithMinorUnits($inputPrice);

        $createdLead = $leadsService->addOne($lead);
        $leadId = $createdLead->getId();
        $fetchedLead = $leadsService->getOne($leadId);

        $this->assertEquals(10.123, $fetchedLead->getPriceWithMinorUnits());
    }

    public function testUpdatePriceToNull()
    {
        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Null Price Lead ' . uniqid())
            ->setPriceWithMinorUnits(50.50);

        $createdLead = $leadsService->addOne($lead);
        $this->assertEquals(50.50, $createdLead->getPriceWithMinorUnits());

        $createdLead->setPriceWithMinorUnits(null);
        $updatedLead = $leadsService->updateOne($createdLead);

        $this->assertEquals(0.0, $updatedLead->getPriceWithMinorUnits());
    }

    public function testAddComplexWithContactsAndCompanies()
    {
        $leadsService = $this->apiClient->leads();
        $collection = new LeadsCollection();

        $lead = new LeadModel();
        $lead->setName('Complex Lead ' . uniqid())
            ->setPriceWithMinorUnits(500.50);
        $contact = new ContactModel();
        $contact->setName('Contact ' . uniqid());
        $contactsCollection = new ContactsCollection();
        $contactsCollection->add($contact);
        $lead->setContacts($contactsCollection);

        $company = new CompanyModel();
        $company->setName('Company ' . uniqid());
        $lead->setCompany($company);

        $collection->add($lead);

        $addedCollection = $leadsService->addComplex($collection);

        $this->assertCount(1, $addedCollection);

        $fetchedLead = $leadsService->getOne($addedCollection[0]->getId(), [LeadModel::CONTACTS, 'company']);

        $this->assertEquals(500.50, $fetchedLead->getPriceWithMinorUnits());
        $this->assertNotNull($fetchedLead->getContacts());
        $this->assertNotNull($fetchedLead->getCompany());
    }

    public function testUpdateComplexWithBulkPriceChanges()
    {
        $leadsService = $this->apiClient->leads();
        $lead1 = new LeadModel();
        $lead1->setName('Bulk Upd 1 ' . uniqid())->setPriceWithMinorUnits(10.00);
        $lead1 = $leadsService->addOne($lead1);

        $lead2 = new LeadModel();
        $lead2->setName('Bulk Upd 2 ' . uniqid())->setPriceWithMinorUnits(20.00);
        $lead2 = $leadsService->addOne($lead2);

        $collection = new LeadsCollection();
        $lead1->setPriceWithMinorUnits(10.11);
        $lead2->setPriceWithMinorUnits(20.22);

        $collection->add($lead1)->add($lead2);

        $updatedCollection = $leadsService->update($collection);
        $this->assertCount(2, $updatedCollection);

        $this->assertEquals(10.11, $updatedCollection[0]->getPriceWithMinorUnits());
        $this->assertEquals(20.22, $updatedCollection[1]->getPriceWithMinorUnits());
    }

    public function testAddOneWithScientificNotation()
    {
        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Scientific ' . uniqid())->setPriceWithMinorUnits(1.5e2);

        $createdLead = $leadsService->addOne($lead);
        $this->assertEquals(150.0, $createdLead->getPriceWithMinorUnits());
    }

    public function testAddAndGetMultiple()
    {
        $leadsService = $this->apiClient->leads();
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $lead = new LeadModel();
            $lead->setName("Multi $i " . uniqid())->setPriceWithMinorUnits(10.0 + $i);
            $created = $leadsService->addOne($lead);
            $ids[] = $created->getId();
        }

        $filter = new LeadsFilter();
        $filter->setIds($ids);
        $collection = $leadsService->get($filter);

        $this->assertCount(3, $collection);
        foreach ($collection as $item) {
            $this->assertContains($item->getId(), $ids);
            $this->assertGreaterThanOrEqual(10.0, $item->getPriceWithMinorUnits());
        }
    }

    public function testAddLeadWithTagsAndPrice()
    {
        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Tags Lead ' . uniqid())
            ->setPriceWithMinorUnits(150.75);

        $tagsCollection = new TagsCollection();
        $tag = new TagModel();
        $tag->setName('PriceTag');
        $tagsCollection->add($tag);

        $lead->setTags($tagsCollection);

        $createdLead = $leadsService->addOne($lead);

        $this->assertEquals(150.75, $createdLead->getPriceWithMinorUnits());
        $this->assertNotNull($createdLead->getTags());
    }

    public function testAddLeadWithContactAndPrice()
    {
        $contactsService = $this->apiClient->contacts();
        $contact = new ContactModel();
        $contact->setName('Contact ' . uniqid());
        $contact = $contactsService->addOne($contact);

        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Contact Lead ' . uniqid())
            ->setPriceWithMinorUnits(200.20);

        $contactsCollection = new ContactsCollection();
        $contactsCollection->add($contact);
        $lead->setContacts($contactsCollection);

        $createdLead = $leadsService->addOne($lead);

        $this->assertEquals(200.20, $createdLead->getPriceWithMinorUnits());
    }

    public function testAddLeadWithCompanyAndPrice()
    {
        $companiesService = $this->apiClient->companies();
        $company = new CompanyModel();
        $company->setName('Company ' . uniqid());
        $company = $companiesService->addOne($company);

        $leadsService = $this->apiClient->leads();
        $lead = new LeadModel();
        $lead->setName('Company Lead ' . uniqid())
            ->setPriceWithMinorUnits(300.30);

        $lead->setCompany($company);

        $createdLead = $leadsService->addOne($lead);

        $this->assertEquals(300.30, $createdLead->getPriceWithMinorUnits());
        $companyModel = $createdLead->getCompany();
        $this->assertNotNull($companyModel);
        $this->assertEquals($company->getId(), $companyModel->getId());
    }
}
