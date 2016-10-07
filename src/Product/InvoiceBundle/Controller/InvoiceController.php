<?php

namespace Product\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;

class InvoiceController extends Controller
{
    /**
     * @Route("/list", name="product_invoice_report")
     * @Template()
     */
    public function listAction()
    {
       //Get Entity Manager 
    	$em         = $this->getDoctrine()->getManager();
        $connection = $em->getConnection();

	//Create Custom Query
    	$statement = $connection->prepare("SELECT * FROM clients");
        $statement->execute();
        
        //Get Records
        $clientRecords = $statement->fetchAll();

        return array('clients' => $clientRecords);
    }

    /**
     * 
     * @Route("/product-list", name="report_product_list_from_client_id",options={"expose"=true}))
     * @Template()
     */
    public function getProductFromClientIdAction() {

    	//Get Entity Manager 
    	$em         = $this->getDoctrine()->getManager();
    	$connection = $em->getConnection();
        $clientId   = $this->get('request')->get('client_id');

	//Create Custom Query
        $statement = $connection->prepare("SELECT * FROM products where client_id =:client_id ");
    	$statement->bindValue('client_id', $clientId);
        $statement->execute();
        
        //Get Records
        $products = $statement->fetchAll();
        
        return new JsonResponse($products);
    }


    /**
     *
     * @Route("/get-report", name="report_product_list",options={"expose"=true}))
     * @Template()
     */
    public function getProductsAction() {

    	//Get Entity Manager 
    	$em = $this->getDoctrine()->getManager();
    	
        //Connection
        $connection = $em->getConnection();

        $clientId     = $this->get('request')->get('client_id');
        $productId    = $this->get('request')->get('product_id');
        $selectedDate = $this->get('request')->get('date');
        $start        = NULL;
        $end          = NULL;
        
    	if ($selectedDate == 'last_month_to_date') {
    		$start = date('Y-m-d', strtotime('first day of previous month'));
    		$end   = date('Y-m-d');
    	}
    	if ($selectedDate == 'this_month') {
    		$start = date('Y-m-d',strtotime('first day of this month')); 
    		$end   = date('Y-m-d'); 
    	}
    	if ($selectedDate == 'this_year') {
                $start = date('Y-01-01'); 
    		$end   = date('Y-12-31'); 
    	} 
    	if ($selectedDate == 'last_year') {
    		$start = date("Y-01-01", strtotime("-1 year"));
                $end   = date("Y-12-t", strtotime($start)); 
    	}


	$productQuery = '';
        $query        = '';
        
    	if ($productId != '') {
    		$productQuery = 'AND pro.product_id ="'.$productId.'" AND invi.product_id ="'.$productId.'"';
    	}

        $query = "SELECT inv.invoice_num, inv.invoice_date, pro.product_id, invi.product_id, pro.product_description, invi.qty, invi.price, (invi.qty * invi.price) as total 
            FROM invoices inv INNER JOIN invoicelineitems invi on inv.invoice_num = invi.invoice_num
            INNER JOIN products pro on pro.product_id = invi.product_id and inv.invoice_num = invi.invoice_num
            where inv.client_id = :client_id and inv.invoice_date BETWEEN :start AND :end ". $productQuery;

    	$statement = $connection->prepare($query);

    	$statement->bindValue('client_id', $clientId);
    	$statement->bindValue('start', $start);
    	$statement->bindValue('end', $end);
        $statement->execute();
	
        $products = $statement->fetchAll();

 	return new JsonResponse($products);
    }
}
