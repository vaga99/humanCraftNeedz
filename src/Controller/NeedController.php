<?php

namespace App\Controller;

use App\Repository\NeedRepository;
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
}
