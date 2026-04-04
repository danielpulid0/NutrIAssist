function selectMeta(element, value) {
    // Remove active from all
    document.querySelectorAll('.meta-card').forEach(el => el.classList.remove('active'));
    // Add active to clicked
    element.classList.add('active');
    // Update hidden input
    document.getElementById('meta_input').value = value;
}
