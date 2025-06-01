/**
 * Service pour gérer l'authentification
 */

/**
 * Connecte un utilisateur
 * @param {Object} credentials - Les identifiants de l'utilisateur
 * @returns {Promise<Object>} - Les données de l'utilisateur
 */
export const login = async (credentials) => {
    try {
        // Afficher les informations sans le mot de passe
        console.log('Tentative de connexion pour:', credentials.username);
        
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(credentials));
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-auth.php?action=login&data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', { 
            success: data.success, 
            message: data.message,
            user: data.user ? { 
                id: data.user.id,
                username: data.user.username,
                role: data.user.role
            } : null
        });
        
        if (!data.success) {
            // Vérifier si c'est un utilisateur par défaut
            const isDefaultUser = ['admin', 'soufiane', 'user'].includes(credentials.username.toLowerCase());
            
            if (isDefaultUser) {
                // Message d'erreur générique sans suggestion de mot de passe
                if (credentials.username.toLowerCase() === 'admin') {
                    throw new Error('Mot de passe incorrect. Contactez l\'administrateur système.');
                } else {
                    throw new Error('Mot de passe incorrect pour le compte par défaut.');
                }
            } else {
                throw new Error(data.message || 'Erreur lors de la connexion');
            }
        }
        
        // Stocker les données utilisateur dans localStorage
        localStorage.setItem('user', JSON.stringify(data.user));
        
        return data.user;
    } catch (error) {
        console.error('Erreur lors de la connexion:', error.message);
        throw error;
    }
};

/**
 * Inscrit un nouvel utilisateur
 * @param {Object} userData - Les données de l'utilisateur à inscrire
 * @returns {Promise<Object>} - Les données de l'utilisateur créé
 */
export const register = async (userData) => {
    try {
        // Afficher les informations sans le mot de passe
        const safeData = { ...userData };
        delete safeData.password;
        console.log('Tentative d\'inscription avec:', safeData);
        
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(userData));
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-auth.php?action=register&data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', { 
            success: data.success, 
            message: data.message
        });
        
        if (!data.success) {
            // Si nous avons des erreurs spécifiques, utilisons-les pour un message plus précis
            if (data.errors) {
                if (data.errors.username) {
                    throw new Error('Ce nom d\'utilisateur est déjà utilisé.');
                } else if (data.errors.email) {
                    throw new Error('Cet email est déjà utilisé.');
                } else if (data.errors.phone) {
                    throw new Error('Ce numéro de téléphone est déjà utilisé.');
                }
            }
            
            // Message générique si aucune erreur spécifique n'est trouvée
            throw new Error(data.message || 'Erreur lors de l\'inscription');
        }
        
        // Stocker les données utilisateur dans localStorage
        localStorage.setItem('user', JSON.stringify(data.user));
        
        return data.user;
    } catch (error) {
        console.error('Erreur lors de l\'inscription:', error.message);
        throw error;
    }
};

/**
 * Vérifie si un nom d'utilisateur est disponible
 * @param {string} username - Le nom d'utilisateur à vérifier
 * @returns {Promise<boolean>} - True si le nom d'utilisateur est disponible, false sinon
 */
export const checkUsernameAvailable = async (username) => {
    try {
        console.log('Vérification de la disponibilité du nom d\'utilisateur:', username);
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-auth.php?action=check-username&username=${encodeURIComponent(username)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', { 
            success: data.success, 
            available: data.available
        });
        
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors de la vérification du nom d\'utilisateur');
        }
        
        return data.available;
    } catch (error) {
        console.error('Erreur lors de la vérification du nom d\'utilisateur:', error.message);
        throw error;
    }
};

/**
 * Déconnecte l'utilisateur
 */
export const logout = () => {
    localStorage.removeItem('user');
}; 