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

        // WIP before authentication
        $author = $authorRepository->findOneById(3);
        $em->persist($author);
        $need->setAuthor($author);
        
        $em->persist($need);
        $em->flush();
        
        $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeeds']);
        return new JsonResponse($jsonNeed, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/needs/{id}', name: 'getNeed', methods: ['GET'])]
    public function getNeed(
        int $id,
        NeedRepository $needRepository, 
        SerializerInterface $serializer,
    ): JsonResponse
    {
        $need = $needRepository->find($id);

        if($need) {
            $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeeds']);
            return new JsonResponse($jsonNeed, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND, []);
    }

    /**
     * Edit Need
     */
    #[Route('/api/needs/{id}', name: 'editNeed', methods: ['PATCH'])]
    public function editNeed(
        int $id, 
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $em,
        NeedRepository $needRepository,
        SkillRepository $skillRepository, 
        AuthorRepository $authorRepository,
    ): JsonResponse
    {
        $need = $needRepository->find($id);

        // Get skills in request
        $content = $request->toArray();

        $title = $content["title"] ?? $need->getTitle();
        $summary = $content["summary"] ?? $need->getSummary();
        $url = $content["url"] ?? $need->getUrl();
        $authorId = $content["author"] ?? $need->getAuthor()->getId();
        $author = $authorRepository->find($authorId);
        $skills = $content["skills"] ?? null;

        $need->setTitle($title);
        $need->setSummary($summary);
        $need->setUrl($url);

        if($author && is_object($author)) {
            $need->setAuthor($author);
        }
        
        if(is_array($skills) && count($skills) > 0) {
            $needSkills = $need->getSkills()->toArray();
            
            // Delete old skills
            if(is_array($needSkills) && count($needSkills) > 0) {
                foreach ($needSkills as $key => $value) {
                    $need->removeSkill($value);
                }
            }
            
            for ($i=0; $i < count($skills) ; $i++) {
                $skill = $skillRepository->find($skills[$i]) ?? null;
                
                // Security check if skills are valid before adding them
                if($skill && is_object($skill)) {
                    $need->addSkill($skill);
                }
            }
        }
        
        $em->persist($need);
        $em->flush();
        $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeeds']);
        return new JsonResponse($jsonNeed, Response::HTTP_OK, [], true);
    }
}
