/*
 * Created on 23/10/2009 by Eduard
 *
 * functions for stats nodes
 */


function guifi_stats_chart01(){   //growth_chart
      v=document.getElementById("edit-zone-id").value;
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart01/0?zone='+v+'">';
}

function guifi_stats_chart02(){   //annualincrement_chart
      v=document.getElementById("edit-zone-id").value;
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart02/0?zone='+v+'">';
}

function guifi_stats_chart03(){   //monthlyaverage_chart
      v=document.getElementById("edit-zone-id").value;
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart03/0?zone='+v+'">';
}

function guifi_stats_chart04(){    //lastyear_chart
      v=document.getElementById("edit-zone-id").value;
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart04/0?zone='+v+'">';
}

function guifi_stats_chart05(nmonths){    //Nodes per month, average of 6 months
      v=document.getElementById("edit-zone-id").value;
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart05/'+nmonths+'?zone='+v+'">';
}

function guifi_stats_chart06(){    //pie areas
      v=document.getElementById("edit-zone-id").value;
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart06/0?zone='+v+'">';
}
function guifi_stats_chart07(){    //Areas with the highest annual increase
      v=document.getElementById("edit-zone-id").value;
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart07/0?zone='+v+'">';
}

