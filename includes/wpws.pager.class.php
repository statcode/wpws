<?php
class WPWS_Pager
{

protected $_limit = NULL;


		//Constructor
		public function __construct($limit)
		{
			$this->_limit = $limit; //initalize limit of records to display
			
		}
		
		public function findStart($curpage) { 
		
			if ((empty($curpage)) || ($curpage == "1")) { 
				$start = 0; 
				$curpage = 1; 
			} else { 
				$start = ($curpage-1) * $this->_limit; 
			} 
			
			return $start; 
		}
		  
		  /*
		   * int findPages (int count) 
		   * Returns the number of pages needed based on a count and a limit 
		   */
		public function countPages($count) { 
			 $pages = (($count % $this->_limit) == 0) ? $count / $this->_limit : floor($count / $this->_limit) + 1; 
		 
			 return $pages; 
		} 
		 
		/* 
		* string pageList (int curpage, int pages) 
		* Returns a list of pages in the format of "« < [pages] > »" 
		**/
		public function pageList($curpage, $pages, $url, $urlArgs) 
		{ 
			$page_list  = ""; 
			
			if ((!isset($curpage)) || ($curpage == "1")) { 
				$curpage= 1; 
			}
		 
		 
		
			/* Print the first and previous page links if necessary */
			if (($curpage != 1) && ($curpage)) { 
			   $page_list .= "  <a href=\"".add_query_arg( $urlArgs, $url )."\"title=\"First Page\"><<</a> "; 
			} 
		 
			if (($curpage-1) > 0) { 
				
				$urlArgs['paged'] = ($curpage-1);
				
			   $page_list .= "<a href=\"".add_query_arg( $urlArgs, $url )."\" title=\"Previous Page\"><</a> "; 
			} 
		 
			/* Print the numeric page list; make the current page unlinked and bold */
			for ($num=1; $num<=$pages; $num++) { 
				if ($num == $curpage) { 
				
					$page_list .= '<span class="page-numbers current">'.$num.'<span>'; 
				} else { 
					
					$urlArgs['paged'] = ($num);
				
					$page_list .= "<a href=\" ".add_query_arg( $urlArgs, $url )."\" title=\"Page ".$num."\" class=\"page-numbers\">".$num."</a>"; 
				} 
				$page_list .= " "; 
			 
			 
			  } 
		
			 /* Print the Next and Last page links if necessary */
			 if (($curpage+1) <= $pages) { 
			 
			 	$urlArgs['paged'] = ($curpage+1);
			 
				$page_list .= "<a href=\"".add_query_arg( $urlArgs, $url )."\" title=\"Next Page\" class=\"next page-numbers\" >></a> "; 
			 } 
		 
			 if (($curpage != $pages) && ($pages != 0)) { 
			 	
				$urlArgs['paged'] = ($pages);
			 
				$page_list .= "<a href=\"".add_query_arg( $urlArgs, $url )."\" title=\"Last Page\">>></a> "; 
			 } 
			 $page_list .= "</td>\n"; 
			 return $page_list; 
		}
		  
		/*
		* string nextPrev (int curpage, int pages, string, url, array $urlArgs) 
		* Returns "Previous | Next" string for individual pagination (it's a word!) 
		*/
		public function nextPrev($curpage, $pages, $url, $urlArgs) { 
		 $next_prev  = ""; 
		 
			if (($curpage-1) <= 0) { 
				$next_prev .= "Previous"; 
			} else { 
			
				//add the final paged query parameter
				$urlArgs['paged'] = ($curpage-1);
			
				$next_prev .= "<a href=\"".add_query_arg( $urlArgs, $url )."\">Previous</a>"; 
			} 
		 
				$next_prev .= " | "; 
		 
			if (($curpage+1) > $pages) { 
				$next_prev .= "Next"; 
			} else { 
			
				//add the final paged query parameter
				$urlArgs['paged'] = ($curpage+1);
				
				$next_prev .= "<a href=\"".add_query_arg( $urlArgs, $url )."\">Next</a>"; 
			} 
				return $next_prev; 
		} 
}

