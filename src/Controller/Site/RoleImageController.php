<?php

namespace App\Controller\Site;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api')]
class RoleImageController extends AbstractController
{
    #[Route('/roles/upload-image', name: 'role_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('image');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        // Validation du type de fichier
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => 'Invalid file type. Only JPEG, PNG, GIF and WebP are allowed.'], 400);
        }

        // Validation de la taille (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'File too large. Maximum size is 5MB.'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/assets/roles',
                $newFilename
            );
        } catch (FileException $e) {
            return $this->json(['error' => 'Failed to upload file'], 500);
        }

        $imageUrl = '/assets/roles/' . $newFilename;

        return $this->json([
            'imageUrl' => $imageUrl,
            'filename' => $newFilename
        ]);
    }

    #[Route('/roles/delete-image', name: 'role_delete_image', methods: ['POST'])]
    public function deleteImage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageUrl = $data['imageUrl'] ?? null;

        if (!$imageUrl) {
            return $this->json(['error' => 'No image URL provided'], 400);
        }

        // Extraire le nom du fichier de l'URL
        $filename = basename($imageUrl);
        $filePath = $this->getParameter('kernel.project_dir') . '/public/assets/roles/' . $filename;

        if (file_exists($filePath)) {
            unlink($filePath);
            return $this->json(['success' => true]);
        }

        return $this->json(['error' => 'File not found'], 404);
    }
}