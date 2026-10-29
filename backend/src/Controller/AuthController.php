<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class AuthController
{
    #[Route('/api/login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return new JsonResponse(
                ['message' => 'Autentificare necesară.'],
                401
            );
        }

        return new JsonResponse([
            'message' => 'Autentificare reușită.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/api/me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return new JsonResponse(
                ['message' => 'Nu ești autentificat.'],
                401
            );
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/csrf/logout', methods: ['GET'])]
    public function logoutToken(
        #[CurrentUser] ?User $user,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        if ($user === null) {
            return new JsonResponse(
                ['message' => 'Nu ești autentificat.'],
                401
            );
        }

        $token = $csrfTokenManager->getToken('admin_logout')->getValue();

        return new JsonResponse(
            ['token' => $token],
            200,
            ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/api/logout', methods: ['POST'])]
    public function logout(
        Request $request,
        #[CurrentUser] ?User $user,
        CsrfTokenManagerInterface $csrfTokenManager,
        TokenStorageInterface $tokenStorage,
    ): JsonResponse {
        if ($user === null) {
            return new JsonResponse(
                ['message' => 'Nu ești autentificat.'],
                401
            );
        }

        $submittedToken = $request->headers->get('X-CSRF-TOKEN', '');

        $csrfToken = new CsrfToken('admin_logout', $submittedToken);

        if (!$csrfTokenManager->isTokenValid($csrfToken)) {
            return new JsonResponse(
                ['message' => 'Token CSRF invalid.'],
                403
            );
        }

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return new JsonResponse([
            'message' => 'Deconectare reușită.',
        ]);
    }


    //Articles

    #[Route('/api/admin/csrf/articles/{id}/edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function articleEditToken(
        int $id,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_article_edit_' . $id)
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }


    #[Route('/api/admin/csrf/articles/create', methods: ['GET'])]
    public function articleCreateToken(
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_article_create')
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/api/admin/csrf/articles/{id}/delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function articleDeleteToken(
        int $id,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_article_delete_' . $id)
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/api/admin/csrf/articles/{id}/image', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function articleImageToken(
        int $id,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_article_image_' . $id)
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }


    //products

    #[Route('/api/admin/csrf/products/{id}/edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function productEditToken(
        int $id,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_product_edit_' . $id)
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/api/admin/csrf/products/create', methods: ['GET'])]
    public function productCreateToken(
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_product_create')
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/api/admin/csrf/products/{id}/image', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function productImageToken(
        int $id,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_product_image_' . $id)
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/api/admin/csrf/products/{id}/delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function productDeleteToken(
        int $id,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_product_delete_' . $id)
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }
}
