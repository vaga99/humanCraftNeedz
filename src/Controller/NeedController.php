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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NeedController extends AbstractController
{
    #[Route('/api/needs', name: 'getNeeds', methods: ['GET'])]
    public function getNeeds(
        Request $request, 
        NeedRepository $needRepository, 
        SerializerInterface $serializer,
    ): JsonResponse
    {

        $title = $request->headers->get('title') ?? null;
        if($title) {
            $needlist = $needRepository->findByTitle($title);
        } else {
            $needlist = $needRepository->findAll();
        }

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
        ValidatorInterface $validator
    ): JsonResponse {
        $content = $request->toArray();
        $authorId = $request->headers->get('AuthorId') ?? null;

        // check if author is allowed to add a need
        if(!$request->headers->get('AuthorId')) {
            throw new BadRequestHttpException("You need to be logged in to add a need");
        } elseif(!$authorRepository->find($authorId)) {
            throw new BadRequestHttpException("You're not allowed to edit this need");
        }

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

        $errors = $validator->validate($need);
        if($errors->count()) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        
        $em->persist($need);
        $em->flush();
        
        $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeeds']);
        return new JsonResponse($jsonNeed, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/needs/{id}', name: 'getNeed', methods: ['GET'])]
    public function getNeed(
        Need $need,
        SerializerInterface $serializer,
    ): JsonResponse
    {
        $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeed']);
        return new JsonResponse($jsonNeed, Response::HTTP_OK, [], true);
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
        ValidatorInterface $validator
    ): JsonResponse
    {
        $need = $needRepository->find($id);
        $content = $request->toArray();
        $authorId = $request->headers->get('AuthorId') ?? null;

        // check if author is allowed to edit a need
        if(!$request->headers->get('AuthorId')) {
            throw new BadRequestHttpException("You need to be logged in to add a need");
        } elseif(!$authorRepository->find($authorId) || $authorRepository->find($authorId) != $need->getAuthor()) {
            throw new BadRequestHttpException("You're not allowed to edit this need");
        }

        // get all request content if present or get current value
        $title = $content["title"] ?? $need->getTitle();
        $summary = $content["summary"] ?? $need->getSummary();
        $url = $content["url"] ?? $need->getUrl();
        $skills = $content["skills"] ?? null;

        // set all properties with new value
        $need->setTitle($title);
        $need->setSummary($summary);
        $need->setUrl($url);
        
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

        $errors = $validator->validate($need);
        if($errors->count()) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        
        $em->persist($need);
        $em->flush();
        $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeeds']);
        return new JsonResponse($jsonNeed, Response::HTTP_OK, [], true);
    }
}
