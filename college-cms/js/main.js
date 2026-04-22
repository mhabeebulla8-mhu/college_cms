const categoryPolicies = {
    "General": {
        title: "Policy & Guidelines",
        subtitle: "When to submit?",
        description: "Submit a complaint when you witness or experience any policy violations.",
        guidelines: [
            "Be factual and clear.",
            "Upload proof if available.",
            "Confidentiality is maintained.",
            "No false complaints allowed."
        ]
    },
    "Anti-Sexual Harassment Cell": {
        title: "Anti-Sexual Harassment Policy",
        subtitle: "About this cell",
        description: "Handles cases related to sexual harassment, misconduct, or any gender-based discrimination.",
        guidelines: [
            "Strict confidentiality is maintained for the complainant.",
            "Immediate interim relief measures can be requested.",
            "Support from counselors is available upon request.",
            "Investigation follows the Vishaka Guidelines and POSH Act."
        ]
    },
    "Anti-Ragging Cell": {
        title: "Anti-Ragging Policy",
        subtitle: "About this cell",
        description: "Strict zero-tolerance policy towards any form of ragging on or off campus.",
        guidelines: [
            "Ragging is a criminal offense as per Supreme Court orders.",
            "Identity of the whistleblower will be kept secret.",
            "Immediate suspension of accused during investigation.",
            "Severe penalties including expulsion and police FIR."
        ]
    },
    "Anti-Harassment Cell": {
        title: "Anti-Harassment Policy",
        subtitle: "About this cell",
        description: "Deals with bullying, mental harassment, or discriminatory behavior by peers or staff.",
        guidelines: [
            "Document specific instances with dates and times.",
            "Mediation services are offered if appropriate.",
            "Protection against retaliation is guaranteed.",
            "Fair hearing for both parties involved."
        ]
    },
    "Grievance Cell": {
        title: "Grievance Cell Policy",
        subtitle: "About this cell",
        description: "For academic issues, examination concerns, or administrative delays.",
        guidelines: [
            "Specify the department or staff member involved.",
            "Attach relevant academic records or correspondence.",
            "Resolution timeline: usually within 7-10 working days.",
            "Appeal process available if not satisfied with outcome."
        ]
    },
    "Hygiene/Facility Cell": {
        title: "Hygiene/Facility Policy",
        subtitle: "About this cell",
        description: "Concerns regarding canteen food, washroom cleanliness, or campus infrastructure.",
        guidelines: [
            "Photos of the facility issue are highly recommended.",
            "Specify the exact location (Block, Floor, Room).",
            "Routine inspections are triggered by valid complaints.",
            "Feedback on resolved issues is encouraged."
        ]
    },
    "Disciplinary Committee": {
        title: "Disciplinary Policy",
        subtitle: "About this cell",
        description: "Handles violations of the student code of conduct and campus rules.",
        guidelines: [
            "Evidence of rule violation must be provided.",
            "Students have the right to represent their case.",
            "Disciplinary actions range from warnings to fines.",
            "Parents may be notified depending on severity."
        ]
    }
};

let isLocked = false;

function updatePolicy(category, force = false) {
    if (isLocked && !force) return;
    
    const policy = categoryPolicies[category] || categoryPolicies["General"];
    
    if (force) {
        isLocked = true;
    }
    
    // Update text content
    document.getElementById('policy-title').innerText = policy.title;
    document.getElementById('policy-subtitle').innerText = policy.subtitle;
    document.getElementById('policy-desc').innerText = policy.description;
    document.getElementById('guidelines-title').innerText = "Specific Guidelines";
    
    // Update list
    const list = document.getElementById('policy-list');
    list.innerHTML = '';
    policy.guidelines.forEach(item => {
        const li = document.createElement('li');
        li.innerText = item;
        list.appendChild(li);
    });

    // Show/hide back button
    const backBtn = document.getElementById('back-btn-container');
    backBtn.style.display = (category === 'General' || !isLocked) ? 'none' : 'block';
    
    if (category === 'General') {
        isLocked = false;
    }
    
    // Update Lodge Complaint link
    const lodgeBtn = document.getElementById('lodge-complaint-btn');
    if (lodgeBtn) {
        if (category === 'General') {
            lodgeBtn.href = 'complaint.php';
        } else {
            lodgeBtn.href = 'complaint.php?category=' + encodeURIComponent(category);
        }
    }

    // Highlight selected box
    const boxes = document.querySelectorAll('.category-box');
    boxes.forEach(box => {
        box.classList.remove('selected');
        if (box.querySelector('h3').innerText === category) {
            box.classList.add('selected');
        }
    });

    // Scroll to policy on mobile
    if (window.innerWidth < 768) {
        document.getElementById('policy').scrollIntoView({ behavior: 'smooth' });
    }
}
