<?php

namespace App\Controller;

use App\Entity\CarAd;
use App\Entity\Garage;

use App\Repository\CarAdRepository;
use App\Repository\GarageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface as EMI;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\String\Slugger\SluggerInterface;






class CarAdController extends AbstractController
{
    /**
     * Exception to naming convention for non-auth-user
     * @Route("/api/classified", name="classified", methods={"GET"})
     * 
     */
    public function index(CarAdRepository $repo): Response
    {
        $classifieds = $repo->findAll();

        $repo->findAll();

        $data = [
            "ads" => $classifieds,
        ];

        return $this->json(
            $data,
            200,
            [],
            [
                // app Entity CarAd "classified" groups
                "groups" => [
                    "classified"
                ]
            ]
        );
    }

    /**
     * Exception to naming convention for non-auth-user
     * @Route("/api/ads/show/{id}", name="show_ad", methods={"GET"})
     * 
     */
    public function show(CarAd $carAd, UserInterface $currentUser): Response
    {
        $isAdmin = in_array("ROLE_ADMIN", $currentUser->getRoles());
        $data = ["user_index" => "SOO SORRY, YOU DON'T HAVE PERMISSION FOR THAT"];

        //A USER HAS ACCESS TO THEIR OWN AD DATA, AS WEL AS AN ADMIN.
        if ($currentUser->getId() == $carAd->getUser()->getId() || $isAdmin) {
            $data = ["car_ad_index" => $carAd];
            //if varified
            return $this->json(
                $data,
                200,
                [],
                [
                    "groups" => [
                        "classified"
                    ]
                ]
            );
        } else {
            //if no permissions
            return $this->json(
                $data,
                200
            );
        }
    }


    /**
     * @Route("/api/ads/byuser", name="ads_user", methods={"GET"})
     * @Route("/api/ads/garage/{id}", name="ads_garage", methods={"GET"})
     * 
     */
    public function ads(Garage $garage = null, UserInterface $currentUser): Response
    {

        if (!$garage) {
            //if user wants to get all their ads
            $classifieds = $currentUser->getCarAds();
        } else {
            if ($currentUser == $garage->getUser()) {
                //if user wants to get all their ads by garage
                $classifieds = $garage->getCarAds();
            } else {
                /**
                 * if user manages to place a wrong garage id 
                 * give them, all their ads instead
                 */
                $classifieds = $currentUser->getCarAds();
            }
        }

        $data = ["ads" => $classifieds];
        return $this->json(
            $data,
            200,
            [],
            [
                // app Entity CarAd "classified" groups
                "groups" => [
                    "classified"
                ]
            ]
        );
    }


    /**
     * create new car ad / announce
     * @Route("/api/ads/new/{garage_id}", name="new_ad", methods={"POST"})
     * 
     */
    public function new(GarageRepository $gRepo, $garage_id, Request $req, SerializerInterface $serializer, EMI $emi, UserInterface $currentUser, SluggerInterface $slugger): Response
    {
        // ONLY AN OWNER CAN REGISTER A NEW CAR AD
        if (in_array("ROLE_OWNER", $currentUser->getRoles())) {

            $data = ["Car_Ad_New" => "That garage doesn't exist"];
            $garage = $gRepo->find($garage_id);
            if ($garage->getUser() == $currentUser) {
                $carAdJson = $req->getContent();

                $carAd = $serializer->deserialize($carAdJson, CarAd::class, 'json');

                // //IMAGE START SECTION
                // $imageFile = $req->files->get('image');

                // $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // // this is needed to safely include the file name as part of the URL
                // $safeFilename = $slugger->slug($originalFilename);
                // $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                // $imageFile->move(
                //     $this->getParameter('car_directory'),
                //     $newFilename
                // );
                // $carAd->setImage($newFilename);
                // // END IMAGE SECTION

                $carAd->setUser($currentUser);
                $carAd->setGarage($garage);
                $emi->persist($carAd);
                $emi->flush();

                $data = ["Car_Ad_New" => $carAd];
            }
            return $this->json(
                $data,
                200,
                [],
                [
                    "groups" => [
                        "classified"
                    ]
                ]
            );
        } else {
            $data = ["user_register" => "SOO SORRY, YOU DON'T HAVE PERMISSION FOR THAT"];
            return $this->json(
                $data,
                200
            );
        }
    }

    /**
     * @Route("/api/ads/edit/{id}", name="edit_ad", methods={"PATCH"})
     * 
     */
    public function edit(CarAd $carAd, Request $req, SerializerInterface $serializer, EMI $emi, UserInterface $currentUser): Response
    {
        $isAdmin = in_array("ROLE_ADMIN", $currentUser->getRoles());
        $isOwner = in_array("ROLE_OWNER", $currentUser->getRoles());

        if ($isOwner || $isAdmin) {

            $data = ["Car_Ad_Edit" => "That car ad doesn't exist"];

            if ($carAd->getUser() == $currentUser || $isAdmin) {
                $carAdJson = $req->getContent();
                $carAdObj = $serializer->deserialize($carAdJson, CarAd::class, 'json');

                // get and set
                $carAd->setTitle($carAdObj->getTitle());
                $carAd->setDescription($carAdObj->getDescription());
                $carAd->setYear($carAdObj->getYear());
                $carAd->setKilometers($carAdObj->getKilometers());
                $carAd->setBrand($carAdObj->getBrand());
                $carAd->setModel($carAdObj->getModel());
                $carAd->setImage($carAdObj->getImage());

                $emi->persist($carAd);
                $emi->flush();

                $data = ["Car_Ad_Edit" => $carAd];
            }
            return $this->json(
                $data,
                200,
                [],
                [
                    "groups" => [
                        "classified"
                    ]
                ]
            );
        } else {
            $data = ["car_edit" => "SOO SORRY, YOU DON'T HAVE PERMISSION FOR THAT"];
            return $this->json(
                $data,
                200
            );
        }
    }

    /**
     * @Route("/api/ads/remove/{id}", name="remove_carAd", methods={"DELETE"})
     * 
     */
    public function remove(CarAd $carAd, EMI $emi, UserInterface $currentUser): Response
    {
        //ONLY A OWNER HAS RIGHTS TO DELETE THEIR OWN GARAGE DATA.
        $isAdmin = in_array("ROLE_ADMIN", $currentUser->getRoles());


        if ($currentUser == $carAd->getUser() || $isAdmin) {

            $emi->remove($carAd);
            $emi->flush();

            $data = ["CarAd_Delete" => "success"];

            return $this->json(
                $data,
                200,
                [],
                [
                    "groups" => [
                        "garage"
                    ]
                ]
            );
        } else {
            $data = ["CarAd_Delete" => "SOO SORRY, YOU DON'T HAVE PERMISSION FOR THAT"];
            return $this->json(
                $data,
                200
            );
        }
    }


    /**
     * 
     * adds image to newly created ads
     * @Route("/api/image/{id}", name="image_carAd", methods={"POST"})
     * 
     */
    public function image(CarAd $carAd, Request $req, SluggerInterface $slugger, EntityManagerInterface $em, UserInterface $currentUser): Response
    {
        $isAdmin = in_array("ROLE_ADMIN", $currentUser->getRoles());
        $isOwner = in_array("ROLE_OWNER", $currentUser->getRoles());

        if ($isOwner || $isAdmin) {

            // $s3 = new \Aws\S3\S3Client([
            //     'version'  => '2006-03-01',
            //     'region'   => 'eu-west-3',
            // ]);

            // $bucket = getenv('S3_CAR_IMAGES_CCAS') ?: die('No "S3_CAR_IMAGES_CCAS" config var in found in env!');
            $imageFile = $req->files->get('image');
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);

            // this is needed to safely include the file name as part of the URL
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
            // $upload = $s3->upload($bucket, $newFilename, fopen($imageFile, 'rb'), 'public-read');

            $imageFile->move(
                $this->getParameter('car_directory'),
                $newFilename
            );

            // $s3Key = $upload->get('ObjectURL');
            $carAd->setImage($newFilename);
            $em->persist($carAd);
            $em->flush();

            $data = ["CarAd_image" => $newFilename];

            return $this->json(
                $data,
                200,
            );
        } else {
            $data = ["CarAd_Image" => "SOO SORRY, YOU DON'T HAVE PERMISSION FOR THAT"];
            return $this->json(
                $data,
                200
            );
        }
    }
}
