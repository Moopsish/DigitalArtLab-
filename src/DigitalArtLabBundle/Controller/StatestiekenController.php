<?php

namespace DigitalArtLabBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * admin controller.
 * @Route("/admin/stats")
 */
class StatestiekenController extends Controller
{

    /**
     * Lists all transaction entities.
     *
     * @Route("/", name="admin_stats")
     * @Method("GET")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $transactions = $em->getRepository('DigitalArtLabBundle:transaction')->findBy([], ['time' => 'DESC']);
        $users = $em->getRepository('DigitalArtLabBundle:User')->findAll();
        $checkins = $em->getRepository('DigitalArtLabBundle:checkin')->findBy([], ['timein' => 'DESC']);

        $time = '00:00:00';
        foreach ($checkins as $checkin){
           if (!is_null($checkin->getSessionduration())){
            $time = sum_the_time($time, date_format($checkin->getSessionduration(), 'H:i:s') );
           }
        }

        $groupsessions = $em->getRepository('DigitalArtLabBundle:checkin')->groupSessions();
        $newsession = array();
        foreach ($groupsessions as $session){
            $dateformat = new \DateTime($session['date']);
            $resultdate = $dateformat->format('d-m-Y');
            array_push($newsession, $resultdate);
        }
        $count = array_count_values($newsession);

        $groupusers = $em->getRepository('DigitalArtLabBundle:User')->groupUsers();
        $newusers = array();
        foreach ($groupusers as $user){
            $dateformat = new \DateTime($user['date']);
            $resultdate = $dateformat->format('d-m-Y');
            array_push($newusers, $resultdate);
        }
        $countusers = array_count_values($newusers);


        $grouptransactions = $em->getRepository('DigitalArtLabBundle:transaction')->groupTransactions();

        $transactiondates = array();
        foreach ($grouptransactions as $transaction){
            $dateformat = new \DateTime($transaction['date']);
            $resultdate = $dateformat->format('d-m-Y');
            array_push($transactiondates, $resultdate);
        }
        $counttransactions = array_count_values( $transactiondates);
        $totaluparray = array();
        $totaldownarray = array();
        foreach ($counttransactions as $key => $date){
            $totalup = 0;
            $totaldown = 0;
            foreach ($grouptransactions as $transaction){
                $convertdate = new \DateTime($transaction['date']);
                $transactionkey = $convertdate->format('d-m-Y');
                if ($key ==  $transactionkey){
                    if ($transaction[0]->getAmount() > 0){
                        $totalup += $transaction[0]->getAmount();
                    }
                    if ($transaction[0]->getAmount() < 0){
                        $totaldown += ($transaction[0]->getAmount()-$transaction[0]->getAmount()-$transaction[0]->getAmount());
                    }
                }

            }
            array_push($totaluparray, array($key => $totalup));
            array_push($totaldownarray, array($key => $totaldown));
        }


        return $this->render('DigitalArtLabBundle:admin:stats.html.twig', array(
            'transactions' => $transactions,
            'users' => $users,
            'checkins' => $checkins,
            'totaaluren' => $time,
            'groupsessions' => $count,
            'createdusers' => $countusers,
            'grouptransactionsup' => $totaluparray,
            'grouptransactionsdown' => $totaldownarray,
        ));
    }


    /**
     * @param Request $request
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function getStatsAction(request $request){

        $em = $this->getDoctrine()->getManager();

        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);


        $time1= $request->request->get('time1');
        $time2= $request->request->get('time2');
        $titel= $request->request->get('titel');

        $time1 = new \DateTime($time1);
        $time1->format('Y-m-d');
        $time2 = new \DateTime($time2);
        $time2->modify('+1 day');
        $time2->format('Y-m-d');

        $transactions = $em->getRepository('DigitalArtLabBundle:transaction')->findBy([], ['time' => 'DESC']);
        $users = $em->getRepository('DigitalArtLabBundle:User')->findAll();
        $checkins = $em->getRepository('DigitalArtLabBundle:checkin')->getSessionsByTime($time1, $time2);


        $time = '00:00:00';
        $avgtime = '00:00:00';
        foreach ($checkins as $checkin){
            if (!is_null($checkin->getSessionduration())){
                $time = sum_the_time($time, date_format($checkin->getSessionduration(), 'H:i:s') );

                $time_divide = explode(':', $time);
                $hours = (int)$time_divide[0];
                $minutes = (int)$time_divide[1];
                $seconds = (int)$time_divide[2];

                $total_seconds = ($hours * 3600) + ($minutes * 60) + $seconds;

                $avgseconds = floor($total_seconds / sizeof($checkins));
                $avgtime = gmdate('H:i:s', $avgseconds);

            }
        }

        $groupsessions = $em->getRepository('DigitalArtLabBundle:checkin')->groupSessionsByTime($time1, $time2);
        $newsession = array();
        foreach ($groupsessions as $session){
            $dateformat = new \DateTime($session['date']);
            $resultdate = $dateformat->format('d-m-Y');
            array_push($newsession, $resultdate);
        }
        $count = array_count_values($newsession);

        $groupusers = $em->getRepository('DigitalArtLabBundle:User')->groupUsersByTime($time1, $time2);
        $newusers = array();
        foreach ($groupusers as $user){
            $dateformat = new \DateTime($user['date']);
            $resultdate = $dateformat->format('d-m-Y');
            array_push($newusers, $resultdate);
        }
        $countusers = array_count_values($newusers);


        $grouptransactions = $em->getRepository('DigitalArtLabBundle:transaction')->groupTransactionsByTime($time1, $time2);

        $transactiondates = array();
        foreach ($grouptransactions as $transaction){
            $dateformat = new \DateTime($transaction['date']);
            $resultdate = $dateformat->format('d-m-Y');
            /*array_push($transactiondates, $transaction['date']);*/
            array_push($transactiondates, $resultdate);
        }
        $counttransactions = array_count_values( $transactiondates);
        $totaluparray = array();
        $totaldownarray = array();
        foreach ($counttransactions as $key => $date){
            $totalup = 0;
            $totaldown = 0;
            foreach ($grouptransactions as $transaction){
                $convertdate = new \DateTime($transaction['date']);
                $transactionkey = $convertdate->format('d-m-Y');
                if ($key ==  $transactionkey){
                    if ($transaction[0]->getAmount() > 0){
                        $totalup += $transaction[0]->getAmount();
                    }
                    if ($transaction[0]->getAmount() < 0){
                        $totaldown += ($transaction[0]->getAmount()-$transaction[0]->getAmount()-$transaction[0]->getAmount());
                    }
                }

            }
            array_push($totaluparray, array($key => $totalup));
            array_push($totaldownarray, array($key => $totaldown));
        }

        $response = array("code" => 100, "success" => true, "time1" => $time1, "time2" => $time2, "titel" => $titel, 'transactions' => $transactions, 'users' => $users, 'checkins' => $checkins, 'totaaluren' => $time, 'groupsessions' => $count, 'createdusers' => $countusers, 'grouptransactionsup' => $totaluparray, 'grouptransactionsdown' => $totaldownarray, 'avgtime' => $avgtime);

        //you can return result as JSON
        return new Response(json_encode($response));
    }


}

function sum_the_time($time1, $time2) {
    $times = array($time1, $time2);
    $seconds = 0;
    foreach ($times as $time)
    {
        list($hour,$minute,$second) = explode(':', $time);
        $seconds += $hour*3600;
        $seconds += $minute*60;
        $seconds += $second;
    }
    $hours = floor($seconds/3600);
    $seconds -= $hours*3600;
    $minutes  = floor($seconds/60);
    $seconds -= $minutes*60;
    // return "{$hours}:{$minutes}:{$seconds}";
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds); // Thanks to Patrick
}

