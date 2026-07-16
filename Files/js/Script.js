var count=0;
var isInitialLoad = true;

var elementMember;
var elementplan;
var elementoverview;
var elementroutine;
var elementpt;

function collapseSidebar() {
	if (isInitialLoad) {
		isInitialLoad = false;
		initializeMember();
	}
	
	var element = document.getElementById("navbarcollapse");
	if (!element) return;
	
	if(count==0){
		element.className = element.className.replace("page-container sidebar-collapsed", "page-container");
		
		if(elementMember) {
			if(memcount==0)
				elementMember.className=elementMember.className.replace("","has-sub");
			else if(memcount==1){
				elementMember.className=elementMember.className.replace("","has-sub opened");
			}
		}

		if(elementplan) {
			if(plancount==0)
				elementplan.className=elementplan.className.replace("","has-sub");
			else if(plancount==1)
				elementplan.className=elementplan.className.replace("","has-sub opened");
		}

		if(elementoverview) {
			if(overviewcount==0)
				elementoverview.className=elementoverview.className.replace("","has-sub");
			else if(overviewcount==1)
				elementoverview.className=elementoverview.className.replace("","has-sub opened");
		}

		if(elementroutine) {
			if(routinecount==0)
				elementroutine.className=elementroutine.className.replace("","has-sub");
			else if(routinecount==1)
				elementroutine.className=elementroutine.className.replace("","has-sub opened");
		}

		if(elementpt) {
			if(ptcount==0)
				elementpt.className=elementpt.className.replace("","has-sub");
			else if(ptcount==1)
				elementpt.className=elementpt.className.replace("","has-sub opened");
		}

		count=1;
		
		// Add mobile backdrop
		if (window.innerWidth < 768) {
			var backdrop = document.getElementById("sidebar-backdrop");
			if (!backdrop) {
				backdrop = document.createElement("div");
				backdrop.id = "sidebar-backdrop";
				backdrop.style.position = "fixed";
				backdrop.style.top = "0";
				backdrop.style.left = "0";
				backdrop.style.width = "100vw";
				backdrop.style.height = "100vh";
				backdrop.style.background = "rgba(0, 0, 0, 0.4)";
				backdrop.style.backdropFilter = "blur(4px)";
				backdrop.style.webkitBackdropFilter = "blur(4px)";
				backdrop.style.zIndex = "9998";
				backdrop.style.cursor = "pointer";
				backdrop.style.transition = "opacity 0.3s ease";
				backdrop.addEventListener("click", collapseSidebar);
				document.body.appendChild(backdrop);
			}
		}
	}
	else if(count==1){
		element.className = element.className.replace("page-container", "page-container sidebar-collapsed");
		
		if(elementMember) {
			if(memcount==0){
				elementMember.className=elementMember.className.replace("has-sub","");
			}else if(memcount==1){
				elementMember.className=elementMember.className.replace("has-sub opened","");
			} 	
		}

		if(elementplan) {
			if(plancount==0)
				elementplan.className=elementplan.className.replace("has-sub","");
			else
				elementplan.className=elementplan.className.replace("has-sub opened","");
		}

		if(elementoverview) {
			if(overviewcount==0)
				elementoverview.className=elementoverview.className.replace("has-sub","");
			else if(overviewcount==1)
				elementoverview.className=elementoverview.className.replace("has-sub opened","");
		}

		if(elementroutine) {
			if(routinecount==0)
				elementroutine.className=elementroutine.className.replace("has-sub","");
			else if(routinecount==1)
				elementroutine.className=elementroutine.className.replace("has-sub opened","");
		}

		if(elementpt) {
			if(ptcount==0)
				elementpt.className=elementpt.className.replace("has-sub","");
			else if(ptcount==1)
				elementpt.className=elementpt.className.replace("has-sub opened","");
		}

		count=0;
		
		// Remove mobile backdrop
		var backdrop = document.getElementById("sidebar-backdrop");
		if (backdrop) {
			backdrop.remove();
		}
	}
}

function initializeMember(){
	elementMember=document.getElementById("hassubopen");
	elementplan=document.getElementById("planhassubopen");
	elementoverview=document.getElementById("overviewhassubopen");
	elementroutine=document.getElementById("routinehassubopen");
	elementpt=document.getElementById("pthassubopen");
}

var memcount=0;
var plancount=0;
var overviewcount=0;
var routinecount=0;
var ptcount=0;

function memberExpand(passvalue){
	initializeMember();

	if(passvalue==1){
		if(memcount==0){
		
		    if(elementplan && plancount==1){
				elementplan.className=elementplan.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("planExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	plancount=0;
		    }
		    if(elementoverview && overviewcount==1){
				elementoverview.className=elementoverview.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("overviewExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	overviewcount=0;
		    }
		    if(elementroutine && routinecount==1){
		    	elementroutine.className=elementroutine.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("routineExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	  routinecount=0;
		    }
		    if(elementpt && ptcount==1){
		    	elementpt.className=elementpt.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("ptExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	  ptcount=0;
		    }

			if(elementMember) {
				elementMember.className=elementMember.className.replace("has-sub","has-sub opened");
				var element=document.getElementById("memExpand");
				if(element) element.className = element.className.replace("", "visible");
				memcount=1;
			}
		}
		else if(memcount==1){
			if(elementMember) {
				elementMember.className=elementMember.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("memExpand");
				if(element) element.className = element.className.replace("visible", "");
				memcount=0;
			}
		}
	}
	else if(passvalue==2){
		if(plancount==0){

			if(elementMember && memcount==1){
				elementMember.className=elementMember.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("memExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	 memcount=0;
		    }
		    if(elementoverview && overviewcount==1){
				elementoverview.className=elementoverview.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("overviewExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	overviewcount=0;
		    }
		    if(elementroutine && routinecount==1){
		    	elementroutine.className=elementroutine.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("routineExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	  routinecount=0;
		    }
		    if(elementpt && ptcount==1){
		    	elementpt.className=elementpt.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("ptExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	  ptcount=0;
		    }
		
			if(elementplan) {
				elementplan.className=elementplan.className.replace("has-sub","has-sub opened");
				var element2=document.getElementById("planExpand");
				if(element2) element2.className = element2.className.replace("", "visible");
				plancount=1;
			}
		}
		else if(plancount==1){
			if(elementplan) {
				elementplan.className=elementplan.className.replace("has-sub opened","has-sub");
				var element2=document.getElementById("planExpand");
				if(element2) element2.className = element2.className.replace("visible", "");
				plancount=0;
			}
		}
	}
	else if(passvalue==3){
		if(overviewcount==0){
		
			if(elementplan && plancount==1){
				elementplan.className=elementplan.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("planExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	plancount=0;
		    }
		    if(elementMember && memcount==1){
				elementMember.className=elementMember.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("memExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	memcount=0;
		    }
		    if(elementroutine && routinecount==1){
		    	elementroutine.className=elementroutine.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("routineExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	  routinecount=0;
		    }
		    if(elementpt && ptcount==1){
		    	elementpt.className=elementpt.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("ptExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	  ptcount=0;
		    }

			if(elementoverview) {
				elementoverview.className=elementoverview.className.replace("has-sub","has-sub opened");
				var element3=document.getElementById("overviewExpand");
				if(element3) element3.className = element3.className.replace("", "visible");
				overviewcount=1;
			}
		}
		else if(overviewcount==1){
			if(elementoverview) {
				elementoverview.className=elementoverview.className.replace("has-sub opened","has-sub");
				var element3=document.getElementById("overviewExpand");
				if(element3) element3.className = element3.className.replace("visible", "");
				overviewcount=0;
			}
		}
	}
	else if(passvalue==4){
		if(routinecount==0){
		
			if(elementplan && plancount==1){
				elementplan.className=elementplan.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("planExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	plancount=0;
		    }
		    if(elementoverview && overviewcount==1){
				elementoverview.className=elementoverview.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("overviewExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	overviewcount=0;
		    }
		    if(elementMember && memcount==1){
				elementMember.className=elementMember.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("memExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	memcount=0;
		    }
		    if(elementpt && ptcount==1){
		    	elementpt.className=elementpt.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("ptExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	  ptcount=0;
		    }

			if(elementroutine) {
				elementroutine.className=elementroutine.className.replace("has-sub","has-sub opened");
				var element4=document.getElementById("routineExpand");
				if(element4) element4.className = element4.className.replace("", "visible");
				routinecount=1;
			}
		}
		else if(routinecount==1){
			if(elementroutine) {
				elementroutine.className=elementroutine.className.replace("has-sub opened","has-sub");
				var element4=document.getElementById("routineExpand");
				if(element4) element4.className = element4.className.replace("visible", "");
				routinecount=0;
			}
		}
	}
	else if(passvalue==5){
		if(ptcount==0){
		
			if(elementplan && plancount==1){
				elementplan.className=elementplan.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("planExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	plancount=0;
		    }
		    if(elementoverview && overviewcount==1){
				elementoverview.className=elementoverview.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("overviewExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	overviewcount=0;
		    }
		    if(elementMember && memcount==1){
				elementMember.className=elementMember.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("memExpand");
				if(element) element.className = element.className.replace("visible", "");
			 	memcount=0;
		    }
		    if(elementroutine && routinecount==1){
				elementroutine.className=elementroutine.className.replace("has-sub opened","has-sub");
				var element=document.getElementById("routineExpand");
				if(element) element.className = element.className.replace("visible", "");
				routinecount=0;
			}

			if(elementpt) {
				elementpt.className=elementpt.className.replace("has-sub","has-sub opened");
				var element5=document.getElementById("ptExpand");
				if(element5) element5.className = element5.className.replace("", "visible");
				ptcount=1;
			}
		}
		else if(ptcount==1){
			if(elementpt) {
				elementpt.className=elementpt.className.replace("has-sub opened","has-sub");
				var element5=document.getElementById("ptExpand");
				if(element5) element5.className = element5.className.replace("visible", "");
				ptcount=0;
			}
		}
	}
}
