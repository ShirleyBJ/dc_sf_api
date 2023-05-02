<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        //Récupére tous les produits
        $products = $em->getRepository(Product::class)->findAll();
        return new JsonResponse($products);
    }

    //READ
    #[Route('/product/{id}', name: 'one_product', methods: ['GET'])]
    public function show($id, Product $product = null, EntityManagerInterface $em): Response
    {
        $product = $em->getRepository(Product::class)->findOneById($id);
        if ($product === null) {
            return new JsonResponse('Produit introuvable', 404); //retourne un status 404 car le 204 en retourne pas de message
        }
        return new JsonResponse($product);
    }

    //ADD
    #[Route('/product', name: 'product_add', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $request, ValidatorInterface $validator): Response
    {
        $product = new Product();
        $product->setTitle($request->get('title')); //récupére le paramétre 'title' de la requête et l'assigne de l'objet
        $product->setPrice($request->get('price'));
        $product->setQuantity($request->get('quantity'));
        $category = $em->getRepository(Category::class)->find($request->get('category'));
        $product->setCategory($category);

        //Fait appel au validator
        $errors = $validator->validate($product); //vérifie que l'objet soit conforme avec les validations demandées(assert)

        if (count($errors)) {
            $e_list = [];
            //S'il y a au moins une erreur
            foreach ($errors as $error) {
                $e_list[] = $error->getMessage(); //on ajoute leur message dans le tableau
            }
            return new JsonResponse($e_list, 400); //on retourne le tableau des messages
        }

        $em->persist($product);
        $em->flush();

        return new JsonResponse('Success', 200);

    }

    //UPDATE
    #[Route('/product/{id}', name: 'product_update', methods: ['PATCH'])]
    public function update(Product $product = null, Request $request, ValidatorInterface $validator, EntityManagerInterface $em){
        if($product === null){
            return new JsonResponse('Produit introuvable', 404);
        }
        $params = 0;
        if($request->get('title') !== null){
            $product->setTitle($request->get('title'));
            $params++;
        }
        if($request->get('price') !== null){
            $product->setPrice($request->get('price'));
            $params++;
        }
        if($request->get('quantity') !== null){
            $product->setQuantity($request->get('quantity'));
            $params++;
        }
        if($request->get('category') !== null){
            $category = $em->getRepository(Category::class)->find($request->get('category'));
            $product->setCategory($category);
            $params++;
        }

        if($params > 0){
            $errors = $validator->validate($product);
            if(count($errors)){
                $e_list = [];
                foreach($errors as $error){
                    $e_list[] = $error->getMessage();
                }
                return new JsonResponse($e_list, 400);
            }
            $em->persist($product);
            $em->flush();
        } else {
            return new JsonResponse('Aucun produit à modifier', 200);
        }
        return new JsonResponse('Success', 200);
    }

    //DELETE
    #[Route('/product/{id}', name: 'product_delete', methods: ['DELETE'])]
    public function delete(Product $product = null, EntityManagerInterface $em){
        if($product === null){
            return new JsonResponse('Produit introuvable', 404);
        }
        $em->remove($product);
        $em->flush();
        return new JsonResponse('Success', 200);
    }
}
