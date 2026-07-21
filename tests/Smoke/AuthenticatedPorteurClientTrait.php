<?php

namespace App\Tests\Smoke;

use App\Entity\User;
use App\Entity\UserDocument;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait AuthenticatedPorteurClientTrait
{
    private const PORTEUR_JOURNEY_EMAIL = 'porteur-journey-test@plateau-urbain.test';

    protected function createAuthenticatedPorteurClient(): KernelBrowser
    {
        $client = static::createClient();
        $porteur = $this->ensurePorteurJourneyUserExists();
        $client->loginUser($porteur);

        return $client;
    }

    private function ensurePorteurJourneyUserExists(): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $repository = $em->getRepository(User::class);
        
        $porteur = $repository->findOneBy(['email' => self::PORTEUR_JOURNEY_EMAIL]);
        if (!$porteur instanceof User) {
            $porteur = new User();
            $porteur->setEmail(self::PORTEUR_JOURNEY_EMAIL);
            $porteur->setEmailCanonical(self::PORTEUR_JOURNEY_EMAIL);
            $porteur->setEnabled(true);
            $porteur->setTypeUser(User::PORTEUR);
            $porteur->setRoles(['ROLE_USER']);
            $hasher = $container->get(UserPasswordHasherInterface::class);
            $porteur->setPassword($hasher->hashPassword($porteur, 'TestPorteurPassword1!'));
            $porteur->setCreatedAt(new \DateTime());
        }
        
        // Base fields
        $porteur->setCivility(User::MISTER);
        $porteur->setFirstname('Journey');
        $porteur->setLastname('Candidat');
        $porteur->setPhone('0123456789');
        
        // Company fields
        $porteur->setCompany('Journey Company');
        $porteur->setCompanyStatus('SAS');
        $porteur->setAddress('456 Candidate Rd');
        $porteur->setZipcode('75002');
        $porteur->setCity('Paris');
        
        // Porteur fields
        $porteur->setBirthday(new \DateTime('1990-01-01'));
        $porteur->setCompanyCreationDate(new \DateTime('2020-01-01'));
        $porteur->setWishedSize(100);
        $useTypeObj = $this->ensureUseTypeExists($em);
        $porteur->setUseType($useTypeObj);
        $porteur->setUsageDate(new \DateTime('2026-08-01'));
        $porteur->setUsageDuration(12);
        $porteur->setPreferredDepartments(['75']);
        $porteur->setMonthlyBudgetMax(1000);

        // Dummy documents to satisfy isProfileComplete()
        if (!$porteur->hasDocuments(UserDocument::ID_TYPE)) {
            $idDoc = new UserDocument();
            $idDoc->setType(UserDocument::ID_TYPE);
            $idDoc->setFileName('dummy_id.pdf');
            $idDoc->setUpdatedAt(new \DateTime());
            $idDoc->setProjectHolder($porteur);
            $porteur->addDocument($idDoc);
            $em->persist($idDoc);
        }

        if (!$porteur->hasDocuments(UserDocument::KBIS_TYPE)) {
            $kbisDoc = new UserDocument();
            $kbisDoc->setType(UserDocument::KBIS_TYPE);
            $kbisDoc->setFileName('dummy_kbis.pdf');
            $kbisDoc->setUpdatedAt(new \DateTime());
            $kbisDoc->setProjectHolder($porteur);
            $porteur->addDocument($kbisDoc);
            $em->persist($kbisDoc);
        }

        $em->persist($porteur);
        $em->flush();

        return $porteur;
    }

    private function ensureUseTypeExists(EntityManagerInterface $em): \App\Entity\UseType
    {
        $type = $em->getRepository(\App\Entity\UseType::class)->findOneBy(['isActive' => true]);
        if ($type instanceof \App\Entity\UseType) {
            return $type;
        }

        $type = new \App\Entity\UseType();
        $type->setName('Bureaux');
        $type->setIsActive(true);
        $em->persist($type);
        $em->flush();

        return $type;
    }
}
