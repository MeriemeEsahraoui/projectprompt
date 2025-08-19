<footer class="bg-slate-900 text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="fade-in-up">
                <div class="flex items-center space-x-2 mb-4">
                    <i class="fas fa-home text-2xl text-indigo-400"></i>
                    <h3 class="text-xl font-bold">Elite Properties</h3>
                </div>
                <p class="text-gray-400 mb-4 text-sm">Your trusted partner in finding the perfect property. We specialize in luxury homes, prime locations, and exceptional service.</p>
                <div class="flex space-x-4 mt-6">
                    <!-- Ajout de vrais liens vers les réseaux sociaux -->
                    <a href="https://facebook.com/eliteproperties" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 transform hover:scale-125 magnetic-button" title="Follow us on Facebook">
                        <i class="fab fa-facebook-f text-lg"></i>
                    </a>
                    <a href="https://twitter.com/eliteproperties" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 transform hover:scale-125 magnetic-button" title="Follow us on Twitter">
                        <i class="fab fa-twitter text-lg"></i>
                    </a>
                    <a href="https://instagram.com/eliteproperties" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 transform hover:scale-125 magnetic-button" title="Follow us on Instagram">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                    <a href="https://linkedin.com/company/eliteproperties" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 transform hover:scale-125 magnetic-button" title="Connect with us on LinkedIn">
                        <i class="fab fa-linkedin-in text-lg"></i>
                    </a>
                </div>
            </div>
            
            <div class="fade-in-up">
                <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <!-- Correction des liens de navigation -->
                    <li><a href="index.php" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 magnetic-button text-sm">Home</a></li>
                    <li><a href="properties.php" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 magnetic-button text-sm">Properties</a></li>
                    <li><a href="about_us.php" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 magnetic-button text-sm">About Us</a></li>
                    <li><a href="contact.php" class="text-gray-400 hover:text-indigo-400 transition-all duration-300 magnetic-button text-sm">Contact</a></li>
                </ul>
            </div>
            
           
            
           
                <!-- Ajout d'un message de confirmation -->
        
    </div>
</footer>

<!-- Ajout du JavaScript pour les fonctionnalités du footer -->
<script>
// Fonction pour gérer la soumission de la newsletter
function handleNewsletterSubmit(event) {
    event.preventDefault();
    
    const email = document.getElementById('newsletterEmail').value;
    const button = document.getElementById('newsletterBtn');
    const message = document.getElementById('newsletterMessage');
    
    // Animation du bouton pendant l'envoi
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    // Simulation d'un appel AJAX (remplacez par votre logique d'envoi)
    setTimeout(() => {
        // Ici vous pouvez ajouter votre logique pour envoyer l'email à votre serveur
        // Par exemple: fetch('newsletter-subscribe.php', { method: 'POST', body: new FormData(event.target) })
        
        // Affichage du message de succès
        message.innerHTML = '<span class="text-green-400">✓ Merci ! Vous êtes maintenant abonné à notre newsletter.</span>';
        message.classList.remove('hidden');
        
        // Réinitialisation du formulaire
        document.getElementById('newsletterEmail').value = '';
        button.innerHTML = '<i class="fas fa-paper-plane"></i>';
        button.disabled = false;
        
        // Masquer le message après 5 secondes
        setTimeout(() => {
            message.classList.add('hidden');
        }, 5000);
        
    }, 1500); // Délai simulé de 1.5 secondes
}

// Fonction pour le scroll smooth vers les sections
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Amélioration des effets magnétiques pour les boutons
document.addEventListener('DOMContentLoaded', function() {
    const magneticButtons = document.querySelectorAll('.magnetic-button');
    
    magneticButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
</script>