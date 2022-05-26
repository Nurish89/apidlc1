<?php

//This model interacts with the database
class ModelProduct extends CI_Model
{
	public function getProductbyProgram($programId)
	{
		$sql = "select p.id productId, p.name, pp.drp price, t.tenureCode, pp.sku, pp.partnerSKU, pb.id brandId, pb.name brandName, c.id categoryId, c.name categoryName from programprice pp, product p, tenure t, brand pb, category c  where pp.programId = ? and pp.productId = p.id and p.isDeleted = 0 and pp.sku is not null and pp.partnerSKU is not null and pp.tenure = t.tenure";
		$products = $this->db->query($sql, array($programId));
		$count = $products->num_rows(); //counting result from query
		if ($count === 0)
		{
			return false;
		}
		else
		{
			return $products->result_array();
		}
	}	

	public function getModelbyProgram($programId)
	{
		$sql = "select b.name brandName, p.model, p.isRelative, p.image, c.id categoryId, b.id brandId, c.name categoryName, b.isRelative brandisRelative, b.image brandImage from product p, brand b, category c where p.id in (select max(id)  from product where id in (select productId from programprice where isDeleted = 0 and programId = ?) and model is not NULL group by model, brandId) and p.brandId = b.id and b.categoryId = c.id";
		//$sql = "select b.name brandName, p.model from product p, brand b where p.brandId = b.id and p.id in (select productId from programprice where isDeleted = 0 and programId = ?) and p.model is not NULL group by p.model, p.brandId ";
		$products = $this->db->query($sql, array($programId));
		$count = $products->num_rows(); //counting result from query
		if ($count === 0)
		{
			return false;
		}
		else
		{
			return $products->result_array();
		}
	}	
	
	public function getModelDetailbyProgram($programId, $model)
        {
		$sql = "select p.name, p.brandId, p.model, p.rom, p.romUnit, p.ram, p.ramUnit, p.color, p.owned, p.isRelative, p.image, pp.id , pp.sku, pp.partnerSKU, pp.productId, pp.programId, pp.tenure , pp.suw, pp.suwUnit, pp.euw, pp.euwUnit, pp.drp, pp.cadc, pp.capf, pp.camrf, pp.dt , pp.cadm, pp.dmf, pp.cpf, pp.fdc, pp.dpv, pp.dpvt, pp.drv, pp.fsv, pp.dmof  from product p, programprice pp where p.model like ? and p.isDeleted =0 and p.id = pp.productId and pp.programId = ? and pp.isDeleted = 0 order by pp.tenure asc";	
                $products = $this->db->query($sql, array($model, $programId));
                $count = $products->num_rows(); //counting result from query
                if ($count === 0)
                {
                        return false;
                }
                else
                {
                        return $products->result_array();
                }
        }		
	
	public function getProductDetailByPartnerSKU($partnerSKU)
	{
		$sql = "select p.id productId, p.name, pp.drp price, pp.tenure, t.tenureCode, pp.sku, pp.partnerSKU from programprice pp, product p, tenure t where pp.partnerSKU = ? and pp.productId = p.id and p.isDeleted = 0 and pp.isDeleted=0 and pp.sku is not null and pp.partnerSKU is not null and pp.tenure = t.tenure";
		$products = $this->db->query($sql, array($partnerSKU));
		$count = $products->num_rows(); //counting result from query
		if ($count === 0)
		{
			return false;
		}
		else
		{
			return $products->result_array();
		}
	}		
	public function getProductDetailBySKU($sku)
	{
		$sql = "select p.id productId, p.name, p.model, p.rom, p.romUnit, p.ram, p.ramUnit, p.color, p.owned, p.isRelative, p.image, b.name brandName, pp.* from programprice pp, product p, tenure t, brand b where p.brandId = b.id and pp.sku = ? and pp.productId = p.id and p.isDeleted = 0 and pp.isDeleted=0 and pp.sku is not null and pp.partnerSKU is not null and pp.tenure = t.tenure";
		$products = $this->db->query($sql, array($sku));
		$count = $products->num_rows(); //counting result from query
		if ($count === 0)
		{
			return false;
		}
		else
		{
			return $products->result_array();
		}
	}		
}


?>
