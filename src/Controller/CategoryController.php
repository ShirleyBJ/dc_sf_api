<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        //Récupére toutes les catégories
        $categories = $em->getRepository(Category::class)->findAll();

        //Retourne au format Json les catégories 
        return new JsonResponse($categories);
    }

    //READ
    #[Route('/category/{id}', name: 'one_category', methods: ['GET'])]
    public function show($id, Category $category = null, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->findOneByIdty($id);
        if ($category === null) {
            return new JsonResponse('Categorie introuvable', 404); //retourne un status 404 car le 204 en retourne pas de message
        }
        return new JsonResponse($category);
    }

    //ADD
    #[Route('/category', name: 'category_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        //TODO: service pour le token et autre service pour l'utilisateurs
        //Avant création d'une catégorie on vérifie si l'utilisateur a le droit d'étre là avec le JWT
        $headers = $request->headers->all(); //on récupére le header
        //si la clé 'token' existe et qu'elle n'es pas vide dans le header
        if (isset($headers['token']) && !empty($headers['token'])) {
            $jwt = current($headers['token']); //récupére la cellule 0 avec current()
            $key = $this->getParameter('jwt_secret');

            //On essaie de décoder le jwt
            try {
                $decoded = JWT::decode($jwt, new Key($key, 'HS256')); //on décode le jwt avec la clé secréte
                //Si la signature n'est pa verifiée ou que la date d'expiration est passée, il entrera dans le catch  
            } catch (\Exception $e) {
                return new JsonResponse($e->getMessage(), 403);
            }

            //On regarde sir le clé 'roles' existe et si l'utilisateur posséde le bon role (ADMIN)
            if ($decoded->roles != null && in_array('ROLE_USER', $decoded->roles)) {

                $category = new Category();
                $category->setName($request->get('name')); //récupére le paramétre 'name' de la requête et l'assigne de l'objet

                //Fait appel au validator
                $errors = $validator->validate($category); //vérifie que l'objet soit conforme avec les validations demandées(assert)

                if (count($errors)) {
                    $e_list = [];
                    //S'il y a au moins une erreur
                    foreach ($errors as $error) {
                        $e_list[] = $error->getMessage(); //on ajoute leur message dans le tableau
                    }
                    return new JsonResponse($e_list, 400); //on retourne le tableau des messages
                }

                $em->persist($category);
                $em->flush();

                return new JsonResponse('Success', 200);
            }
        }

        return new JsonResponse('Access denied', 403);
    }

    //UDPATE
    #[Route('/category/{id}', name: 'category_update', methods: ['PATCH'])]
    public function update(Category $category = null, Request $request, ValidatorInterface $validator, EntityManagerInterface $em): Response
    {
        if ($category === null) {
            return new JsonResponse('Categorie introuvable', 404); //retourne un status 404 car le 204 en retourne pas de message
        }
        $params = 0;

        //On regarde si l'attribut name reçu n'est pas null
        if ($request->get('name') != null) {
            //On attribue à la catégory le nouveau name
            $category->setName($request->get('name'));
            $params++;
        }

        if ($params > 0) {
            $errors = $validator->validate($category); //vérifie que l'objet soit conforme avec les validations demandées(assert)

            if (count($errors) > 0) {
                $e_list = [];
                //S'il y a au moins une erreur
                foreach ($errors as $error) {
                    $e_list[] = $error->getMessage(); //on ajoute leur message dans le tableau
                }
                return new JsonResponse($e_list, 400); //on retourne le tableau des messages
            }
            $em->persist($category);
            $em->flush();
        } else {
            return new JsonResponse('Aucun paramètre à modifier', 200);
        }
        return new JsonResponse('Success', 200);
    }

    //DELETE
    #[Route('/category/{id}', name: 'category_delete', methods: ['DELETE'])]
    public function delete(Category $category = null, EntityManagerInterface $em): Response
    {
        if ($category === null) {
            return new JsonResponse('Categorie introuvable', 404); //retourne un status 404 car le 204 en retourne pas de message
        }
        $em->remove($category);
        $em->flush();

        return new JsonResponse('Catégorie supprimée', 200);
    }

    //Gestion d'upload de fichier
    #[Route('/file', name: 'upload_file', methods: ['POST'])]
    public function upload(Request $request): Response{
        //Récupération du fichier
        $image = $request->files->get('image');

        if($image) {
            $newFilename = uniqid().'.'.$image->guessExtension();

            //Move the file to the directory where brochures are stored
            try {
                $image->move(
                    $this->getParameter('upload_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                //.. handle exception if something happens during file upload
                return new JsonResponse($e->getMessage(), 400);
            }
            //Updates the 'brochureFilename' property to stor the PDF file name
            //instead of its content
            return new JsonResponse('Fichier uploadé', 200);
        }
        return new JsonResponse('Aucun fichier reçu', 400);
    }
}
