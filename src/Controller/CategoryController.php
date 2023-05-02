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
    public function show($id, Category $category = null , EntityManagerInterface $em): Response
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
}
