<?php

namespace App\Controller;

use App\Entity\Need;
use App\Repository\AuthorRepository;
use App\Repository\NeedRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NeedController extends AbstractController
{
    #[Route('/api/needs', name: 'getNeeds', methods: ['GET'])]
    public function getNeeds(
        NeedRepository $needRepository, 
        SerializerInterface $serializer,
    ): JsonResponse
    {
        $needlist = $needRepository->findAll();

        $jsonNeedList = $serializer->serialize($needlist, 'json', ['groups' => 'getNeeds']);

        return new JsonResponse($jsonNeedList, Response::HTTP_OK, [], true);
    }

    /**
     * Add Need
     */
    #[Route('/api/needs', name: 'createNeed', methods: ['POST'])]
    public function createNeed(
        Request $request, 
        SerializerInterface $serializer, 
        SkillRepository $skillRepository, 
        AuthorRepository $authorRepository,
        EntityManagerInterface $em, 
    ): JsonResponse {
        $content = $request->toArray();

        $skills = $content['skills'] ?? null;
        $need = new Need();

        $need->setTitle($content["title"]);
        $need->setSummary($content["summary"]);
        $need->setUrl($content["url"]);
        
        if(is_array($skills) && count($skills) > 0) {
            for ($i=0; $i < count($skills) ; $i++) {
                
                $skill = $skillRepository->find($skills[$i]) ?? null;
                
                if($skill && is_object($skill)) {
                    $need->addSkill($skill);
                }
            }
        }

        // WIP avant l'authentification
        $author = $authorRepository->findOneById(3);
        $em->persist($author);
        $need->setAuthor($author);
        
        $em->persist($need);
        $em->flush();
        
        $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeeds']);
        return new JsonResponse($jsonNeed, Response::HTTP_CREATED, [], true);
    }
}
