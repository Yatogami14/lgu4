/**
 * Shared actions for business application management
 * Used in verify_business_owners.php and business_applications.php
 */

function openRejectModal(businessId, businessName) {
    document.getElementById('reject_business_id').value = businessId;
    document.getElementById('rejectBusinessName').textContent = businessName;
    openModal('rejectModal');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
    }
    
    // If closing the document viewer, clear its content to stop videos/PDFs
    // This element ID is specific to verify_business_owners.php but safe to check here
    if (modalId === 'documentViewerModal') {
        const viewerContent = document.getElementById('documentViewerContent');
        if (viewerContent) viewerContent.innerHTML = '';
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function populateRevisionModal(businessId, businessName, documents) {
    document.getElementById('revision_business_id').value = businessId;
    document.getElementById('revisionBusinessName').textContent = businessName;
    
    const docList = document.getElementById('documentList');
    docList.innerHTML = '';
    
    if (documents && documents.length > 0) {
        documents.forEach(doc => {
            const html = `
                <div class="border-b pb-2">
                    <div class="flex items-center justify-between">
                        <label class="font-medium text-sm text-gray-700">${doc.type}</label>
                        <select name="doc_status[${doc.id}]" class="text-sm border-gray-300 rounded" onchange="this.nextElementSibling.classList.toggle('hidden', this.value !== 'rejected')">
                            <option value="pending">OK</option>
                            <option value="rejected">Reject / Request Revision</option>
                        </select>
                    </div>
                    <textarea name="doc_feedback[${doc.id}]" placeholder="Reason for rejection..." class="mt-2 w-full text-sm border-gray-300 rounded hidden" rows="2"></textarea>
                </div>
            `;
            docList.insertAdjacentHTML('beforeend', html);
        });
    } else {
        docList.innerHTML = '<p class="text-center text-gray-500">No documents found for this application.</p>';
    }
}