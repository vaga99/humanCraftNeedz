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
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

class NeedController extends AbstractController
{
    /**
     * Get all needs with an optional parameter filter in query to search for title
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param NeedRepository $needRepository
     * @return JsonResponse
     */
    #[Route('/api/needs', name: 'getNeeds', methods: ['GET'])]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: "Optionnal search in need's title",
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Need::class, groups: ['getNeeds'])
    )]
    #[OA\Tag(name: 'Needs')]
    #[Security(name: 'Author')]
    public function getNeeds(
        Request $request, 
        SerializerInterface $serializer,
        NeedRepository $needRepository, 
    ): JsonResponse
    {
        $title = $request->query->get('search') ?? null;
        
        if($title) {
            $needlist = $needRepository->findByTitle($title);
        } else {
            $needlist = $needRepository->findAll();
        }

        $jsonNeedList = $serializer->serialize($needlist, 'json', ['groups' => 'getNeeds']);

        return new JsonResponse($jsonNeedList, Response::HTTP_OK, [], true);
    }

    /**
     * Add a need if the user is an authenticated Author
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param SkillRepository $skillRepository
     * @param AuthorRepository $authorRepository
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/needs', name: 'addNeed', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: Need::class, groups: ["addNeed"]))]
    #[OA\Parameter(
        name: 'AuthorId',
        in: 'header',
        description: "Author id for creating need",
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Need::class, groups: ['getNeeds'])
    )]
    #[OA\Tag(name: 'Needs')]
    #[Security(name: 'Author')]
    public function addNeed(
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
            throw new BadRequestHttpException("You're not allowed to add a need");
        }

        $skills = $content['skills'] ?? null;
        $need = new Need();

        $title = $content["title"] ?? "";
        $summary = $content["summary"] ?? "";
        $url = $content["url"] ?? "";

        $need->setTitle($title);
        $need->setSummary($summary);
        $need->setUrl($url);
        
        if(is_array($skills) && count($skills) > 0) {
            for ($i=0; $i < count($skills) ; $i++) {
                
                $skill = $skillRepository->find($skills[$i]) ?? null;
                
                if($skill && is_object($skill)) {
                    $need->addSkill($skill);
                }
            }
        }

        $author = $authorRepository->findOneById($authorId);
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

    /**
     * Get a single need
     * 
     * @param Need $need
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/needs/{need}', name: 'getNeed', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Need::class, groups: ['getNeed'])
    )]
    #[OA\Tag(name: 'Needs')]
    #[Security(name: 'Author')]
    public function getNeed(
        Need $need,
        SerializerInterface $serializer,
    ): JsonResponse
    {
        $jsonNeed = $serializer->serialize($need, 'json', ['groups' => 'getNeed']);
        return new JsonResponse($jsonNeed, Response::HTTP_OK, [], true);
    }

    /**
     * Add a need if the user is an authenticated Author
     * 
     * @param int $id
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param SkillRepository $skillRepository
     * @param AuthorRepository $authorRepository
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/needs/{need}', name: 'editNeed', methods: ['PATCH'])]
    #[OA\RequestBody(content: new Model(type: Need::class, groups: ["addNeed"]))]
    #[OA\Parameter(
        name: 'AuthorId',
        in: 'header',
        description: "Author id for editing need (must be the one that created the need)",
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Need::class, groups: ['getNeed'])
    )]
    #[OA\Tag(name: 'Needs')]
    #[Security(name: 'Author')]
    public function editNeed(
        Need $need, 
        Request $request, 
        SerializerInterface $serializer, 
        SkillRepository $skillRepository, 
        AuthorRepository $authorRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
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
