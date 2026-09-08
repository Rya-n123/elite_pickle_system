// assets/js/scanner.js

let html5QrcodeScanner;
let scannedMemberId = null; 

function onScanSuccess(decodedText, decodedResult) {
    html5QrcodeScanner.clear();
    
    document.getElementById('rescan-btn').style.display = 'block';
    document.getElementById('member-profile').style.display = 'block';
    document.getElementById('action-panel').style.display = 'none'; 
    document.getElementById('member-name').innerText = "Verifying member...";
    
    fetch(`api/fetch_member.php?qr_code=${encodeURIComponent(decodedText)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                scannedMemberId = data.member.id;
                document.getElementById('member-name').innerText = `${data.member.first_name} ${data.member.last_name}`;
                document.getElementById('member-points').innerText = data.member.point_balance;
                
                if (data.member.is_eligible) {
                    document.getElementById('member-lockout').innerText = "Eligible Now";
                    document.getElementById('member-lockout').style.color = "#4ade80"; 
                } else {
                    document.getElementById('member-lockout').innerText = data.member.next_eligible_date;
                    document.getElementById('member-lockout').style.color = "#ff6b6b"; 
                }

                // RULE: Block Suspended Accounts
                if (data.member.status === 'Suspended') {
                    Swal.fire({
                        title: 'Account Suspended',
                        text: 'This VIP member is currently suspended. No transactions are allowed.',
                        icon: 'error',
                        background: '#1A2A47',
                        color: '#ffffff',
                        confirmButtonColor: '#ff6b6b'
                    });
                    document.getElementById('member-profile').style.display = 'none';
                    startScanner();
                    return; // Pigilan ang pagpapatuloy sa physical card validation
                }

                // RULE 5 & 8: SweetAlert Physical Card Verification
                Swal.fire({
                    title: 'Verify Membership',
                    html: `Is the physical VIP Card for <br><strong style="color:#D4AF37; font-size:20px;">${data.member.first_name} ${data.member.last_name}</strong> <br>presented right now?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#D4AF37',
                    cancelButtonColor: '#ff6b6b',
                    confirmButtonText: 'Yes, Card Presented',
                    cancelButtonText: 'No / Digital Only',
                    background: '#1A2A47',
                    color: '#ffffff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('action-panel').style.display = 'flex';
                        
                        // Fetch Dynamic Activities
                        fetch('api/fetch_activities.php')
                            .then(res => res.json())
                            .then(actData => {
                                const btnContainer = document.getElementById('dynamic-activity-buttons');
                                btnContainer.innerHTML = '';
                                
                                actData.activities.forEach(act => {
                                    const btn = document.createElement('button');
                                    btn.className = 'btn-gold';
                                    if (act.has_daily_limit == 1) btn.classList.add('btn-secondary');
                                    
                                    const limitText = act.has_daily_limit == 1 ? ' (1/day)' : '';
                                    btn.innerText = `+ Add ${act.activity_name} (${act.points_awarded}pt)${limitText}`;
                                    
                                    btn.onclick = () => addPoint(act.activity_name, act.points_awarded);
                                    btnContainer.appendChild(btn);
                                });
                            });
                    } else {
                        Swal.fire({
                            title: 'Points Denied',
                            text: 'No points can be awarded without the physical VIP card.',
                            icon: 'error',
                            background: '#1A2A47',
                            color: '#ffffff',
                            confirmButtonColor: '#D4AF37'
                        });
                        document.getElementById('member-profile').style.display = 'none';
                        startScanner(); // Reset scanner
                    }
                });

            } else {
                Swal.fire('Not Found', 'This QR Code is not registered in the system.', 'error');
                document.getElementById('member-profile').style.display = 'none';
                startScanner();
            }
        })
        .catch(error => console.error('Error fetching member:', error));
}

function onScanFailure(error) {}

function startScanner() {
    document.getElementById('rescan-btn').style.display = 'none';
    document.getElementById('member-profile').style.display = 'none';
    
    html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { fps: 10, qrbox: {width: 250, height: 250} },
        false
    );
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
}

document.addEventListener("DOMContentLoaded", () => {
    startScanner();
});

document.getElementById('rescan-btn').addEventListener('click', () => {
    scannedMemberId = null;
    startScanner();
});

// Function para mag-add ng point (kokonekta sa process_point.php)
function addPoint(activityType, pointsAwarded) {
    if (!scannedMemberId) return;

    Swal.fire({
        title: `Add ${pointsAwarded} Point(s)?`,
        text: `Confirm adding points for ${activityType}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#D4AF37',
        cancelButtonColor: '#334155',
        confirmButtonText: 'Confirm Addition',
        background: '#1A2A47',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/process_point.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    member_id: scannedMemberId,
                    activity_type: activityType,
                    points_awarded: pointsAwarded
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: `1 point added for ${activityType}.`,
                        icon: 'success',
                        background: '#1A2A47',
                        color: '#ffffff',
                        confirmButtonColor: '#D4AF37'
                    });
                    // I-update agad ang balance sa screen
                    document.getElementById('member-points').innerText = data.new_balance;
                } else {
                    Swal.fire({
                        title: 'Notice',
                        text: data.error,
                        icon: 'info',
                        background: '#1A2A47',
                        color: '#ffffff',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            });
        }
    });
}

// Function para buksan ang modal at kunin ang rewards
function openRedeemModal() {
    if (!scannedMemberId) return;

    const currentPoints = parseInt(document.getElementById('member-points').innerText);
    const lockoutStatus = document.getElementById('member-lockout').innerText;

    // RULE 11 Check on UI: Block kung hindi pa "Eligible Now"
    if (lockoutStatus !== "Eligible Now") {
        Swal.fire('Not Eligible', 'This member is still on the 2-month cooldown period.', 'warning');
        return;
    }

    document.getElementById('redeem-modal').style.display = 'flex';
    
    // Kunin ang active rewards sa database
    fetch('api/fetch_rewards.php')
        .then(response => response.json())
        .then(data => {
            const dropdown = document.getElementById('reward-dropdown');
            dropdown.innerHTML = '<option value="">-- Select a Reward --</option>';
            
            data.rewards.forEach(reward => {
                const option = document.createElement('option');
                option.value = reward.id;
                option.innerText = `${reward.reward_name} (${reward.points_required} pts)`;
                
                // Disable yung option kung kulang ang points
                if (currentPoints < reward.points_required) {
                    option.disabled = true;
                    option.innerText += ' - Insufficient Points';
                }
                
                dropdown.appendChild(option);
            });
        });
}

function closeRedeemModal() {
    document.getElementById('redeem-modal').style.display = 'none';
}

function submitRedemption() {
    const rewardId = document.getElementById('reward-dropdown').value;
    
    if (!rewardId) {
        Swal.fire('Error', 'Please select a reward first.', 'error');
        return;
    }

    Swal.fire({
        title: 'Final Confirmation',
        html: 'Are you sure? <br><br><b>ALL remaining points will be forfeited and reset to 0.</b>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff6b6b',
        cancelButtonColor: '#334155',
        confirmButtonText: 'Yes, REDEEM & RESET',
        background: '#1A2A47',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            closeRedeemModal();
            
            fetch('api/process_redemption.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    member_id: scannedMemberId,
                    reward_id: rewardId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Reward Redeemed!',
                        html: `Points successfully reset to 0.<br><br>Next Eligible Date: <strong style="color:#4ade80">${data.next_eligible}</strong>`,
                        icon: 'success',
                        background: '#1A2A47',
                        color: '#ffffff',
                        confirmButtonColor: '#D4AF37'
                    });
                    
                    // I-update ang UI papuntang 0
                    document.getElementById('member-points').innerText = '0';
                    document.getElementById('member-lockout').innerText = data.next_eligible;
                    document.getElementById('member-lockout').style.color = "#ff6b6b";
                } else {
                    Swal.fire('Transaction Failed', data.error, 'error');
                }
            });
        }
    });
}

// --- Search Module Logic ---

function searchMember() {
    const query = document.getElementById('search-input').value.trim();
    if (!query) return;

    const resultsContainer = document.getElementById('search-results');
    resultsContainer.innerHTML = '<div style="padding:15px; color:#cbd5e1; text-align:center;">Searching...</div>';
    resultsContainer.style.display = 'block';

    fetch(`api/search_member.php?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            resultsContainer.innerHTML = '';
            
            if (data.success && data.results.length > 0) {
                data.results.forEach(member => {
                    const item = document.createElement('div');
                    item.className = 'search-result-item';
                    
                    // Kapag na-click, ipapasa nito yung QR Code sa onScanSuccess para parehas ang flow
                    item.onclick = () => {
                        resultsContainer.style.display = 'none';
                        document.getElementById('search-input').value = '';
                        onScanSuccess(member.qr_code, null); // Reuse scan logic
                    };

                    const statusBadge = member.status === 'Suspended' 
                        ? '<span style="color:#ff6b6b; font-size:10px; border:1px solid #ff6b6b; padding:2px 5px; border-radius:10px; margin-left:5px;">Suspended</span>' 
                        : '';
                    
                    item.innerHTML = `
                        <div>
                            <strong style="color: #fff;">${member.first_name} ${member.last_name}</strong> ${statusBadge}
                            <div style="font-size: 12px;">ID: ${member.id} | QR: ${member.qr_code}</div>
                        </div>
                        <div style="color: #D4AF37; font-weight: bold;">
                            ${member.point_balance} pts
                        </div>
                    `;
                    resultsContainer.appendChild(item);
                });
            } else {
                resultsContainer.innerHTML = '<div style="padding:15px; color:#ff6b6b; text-align:center;">No members found.</div>';
            }
        })
        .catch(error => {
            resultsContainer.innerHTML = '<div style="padding:15px; color:#ff6b6b; text-align:center;">System Error.</div>';
        });
}

// Debounce search function: maghihintay ng 500ms (kalahating segundo) matapos mag-type bago mag-query
let searchTimeout = null;

document.getElementById('search-input').addEventListener('input', function (e) {
    clearTimeout(searchTimeout);
    
    // Kapag binura ng user lahat ng text, itago agad ang dropdown
    if (this.value.trim() === '') {
        document.getElementById('search-results').style.display = 'none';
        return;
    }

    // I-set ang timer para sa pag-execute ng search
    searchTimeout = setTimeout(() => {
        searchMember();
    }, 500);
});

// Itago ang dropdown kapag nag-click sa labas
document.addEventListener('click', function(event) {
    const searchContainer = document.querySelector('.search-container');
    const resultsContainer = document.getElementById('search-results');
    if (!searchContainer.contains(event.target)) {
        resultsContainer.style.display = 'none';
    }
});