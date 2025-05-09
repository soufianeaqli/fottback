import React, { useState, useEffect } from 'react';

function Contact({ user }) {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        subject: '',
        message: ''
    });
    const [confirmationMessage, setConfirmationMessage] = useState('');
    const [contactMessages, setContactMessages] = useState([]);
    
    // Charger les messages existants au chargement du composant
    useEffect(() => {
        const savedMessages = localStorage.getItem('contactMessages');
        if (savedMessages) {
            setContactMessages(JSON.parse(savedMessages));
        }
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prevState => ({
            ...prevState,
            [name]: value
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Créer un nouveau message avec un ID unique et une date
        const newMessage = {
            id: Date.now(),
            date: new Date().toLocaleString(),
            ...formData,
            read: false
        };
        
        // Ajouter le message à la liste
        const updatedMessages = [...contactMessages, newMessage];
        setContactMessages(updatedMessages);
        
        // Enregistrer dans localStorage
        localStorage.setItem('contactMessages', JSON.stringify(updatedMessages));
        
        // Afficher le message de confirmation
        setConfirmationMessage('Votre message a été envoyé avec succès !');
        
        // Réinitialiser le formulaire
        setFormData({
            name: '',
            email: '',
            subject: '',
            message: ''
        });
        
        setTimeout(() => {
            setConfirmationMessage('');
        }, 5000);
    };
    
    const markAsRead = (id) => {
        const updatedMessages = contactMessages.map(msg => 
            msg.id === id ? { ...msg, read: true } : msg
        );
        setContactMessages(updatedMessages);
        localStorage.setItem('contactMessages', JSON.stringify(updatedMessages));
    };
    
    const deleteMessage = (id) => {
        const updatedMessages = contactMessages.filter(msg => msg.id !== id);
        setContactMessages(updatedMessages);
        localStorage.setItem('contactMessages', JSON.stringify(updatedMessages));
    };

    return (
        <div className="contact-container">
            {user && user.role === 'admin' ? (
                <div className="admin-messages">
                    <h1>Messages reçus</h1>
                    {contactMessages.length === 0 ? (
                        <div className="no-messages">
                            <i className="fas fa-inbox"></i>
                            <p>Aucun message reçu pour le moment.</p>
                        </div>
                    ) : (
                        <div className="messages-list">
                            {contactMessages.map(msg => (
                                <div key={msg.id} className={`message-card ${!msg.read ? 'unread' : ''}`}>
                                    <div className="message-header">
                                        <h3>{msg.subject}</h3>
                                        <div className="message-meta">
                                            <span className="message-date">{msg.date}</span>
                                            {!msg.read && <span className="new-badge">Nouveau</span>}
                                        </div>
                                    </div>
                                    <div className="sender-info">
                                        <div><strong>De:</strong> {msg.name}</div>
                                        <div><strong>Email:</strong> {msg.email}</div>
                                    </div>
                                    <div className="message-content">{msg.message}</div>
                                    <div className="message-actions">
                                        {!msg.read && (
                                            <button 
                                                className="action-btn read-btn" 
                                                onClick={() => markAsRead(msg.id)}
                                            >
                                                <i className="fas fa-check"></i> Marquer comme lu
                                            </button>
                                        )}
                                        <button 
                                            className="action-btn delete-btn" 
                                            onClick={() => deleteMessage(msg.id)}
                                        >
                                            <i className="fas fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            ) : (
                <>
                    <div className="contact-info">
                        <h1>Contactez-nous</h1>
                        <div className="info-section">
                            <div className="info-item">
                                <i className="fas fa-map-marker-alt"></i>
                                <h3>Notre Adresse</h3>
                                <p>Bab Tizimi</p>
                                <p>Meknes, Maroc</p>
                            </div>
                            <div className="info-item">
                                <i className="fas fa-phone"></i>
                                <h3>Téléphone</h3>
                                <p>+212 6 12 34 56 78</p>
                                <p>+212 5 22 33 44 55</p>
                            </div>
                            <div className="info-item">
                                <i className="fas fa-envelope"></i>
                                <h3>Email</h3>
                                <p>contact@terrainsport.com</p>
                                <p>info@terrainsport.com</p>
                            </div>
                        </div>
                    </div>

                    <div className="contact-form">
                        <h2>Envoyez-nous un message</h2>
                        {confirmationMessage && (
                            <div className="confirmation-message">
                                {confirmationMessage}
                            </div>
                        )}
                        <form onSubmit={handleSubmit}>
                            <div className="form-group">
                                <label>Nom complet</label>
                                <input
                                    type="text"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleChange}
                                    required
                                    placeholder="Votre nom"
                                />
                            </div>
                            <div className="form-group">
                                <label>Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    value={formData.email}
                                    onChange={handleChange}
                                    required
                                    placeholder="Votre email"
                                />
                            </div>
                            <div className="form-group">
                                <label>Sujet</label>
                                <input
                                    type="text"
                                    name="subject"
                                    value={formData.subject}
                                    onChange={handleChange}
                                    required
                                    placeholder="Sujet de votre message"
                                />
                            </div>
                            <div className="form-group">
                                <label>Message</label>
                                <textarea
                                    name="message"
                                    value={formData.message}
                                    onChange={handleChange}
                                    required
                                    placeholder="Votre message"
                                    rows="5"
                                ></textarea>
                            </div>
                            <button type="submit" className="submit-btn">
                                <i className="fas fa-paper-plane"></i> Envoyer le message
                            </button>
                        </form>
                    </div>
                </>
            )}
        </div>
    );
}

export default Contact;