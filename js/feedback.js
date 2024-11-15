document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedbackForm');
    const feedbackList = document.getElementById('feedbackList');
    let lastId = 0;
    let isLoading = false;

    // Format date for display
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    // Create feedback item HTML
    function createFeedbackItem(feedback) {
        return `
            <div class="feedback-item" data-id="${feedback.id}">
                <div class="feedback-name">${feedback.name}</div>
                <div class="feedback-comment">${feedback.comment}</div>
                <div class="feedback-date">${formatDate(feedback.created_at)}</div>
            </div>
        `;
    }

    // Load feedback
    async function loadFeedback() {
        if (isLoading) return;
        isLoading = true;

        try {
            const response = await fetch(`get_feedback.php?lastId=${lastId}`);
            const data = await response.json();

            if (data.status === 'success' && data.data.length > 0) {
                data.data.forEach(feedback => {
                    feedbackList.insertAdjacentHTML('beforeend', createFeedbackItem(feedback));
                    lastId = feedback.id;
                });
            }
        } catch (error) {
            console.error('Error loading feedback:', error);
        } finally {
            isLoading = false;
        }
    }

    // Handle form submission
    feedbackForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitButton = feedbackForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        const formData = new FormData(feedbackForm);

        try {
            const response = await fetch('submit_feedback.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Clear form
                feedbackForm.reset();
                
                // Reload feedback
                lastId = 0;
                feedbackList.innerHTML = '';
                await loadFeedback();
                
                alert('Thank you for your feedback!');
            } else {
                alert(data.message || 'Error submitting feedback');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error submitting feedback. Please try again.');
        } finally {
            submitButton.disabled = false;
        }
    });

    // Initial load
    loadFeedback();

    // Infinite scroll
    feedbackList.addEventListener('scroll', function() {
        if (feedbackList.scrollHeight - feedbackList.scrollTop <= feedbackList.clientHeight + 100) {
            loadFeedback();
        }
    });
});
