-- Create tables for the Symptom Checker feature

-- Table for storing symptoms
CREATE TABLE IF NOT EXISTS symptoms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for storing diseases/conditions
CREATE TABLE IF NOT EXISTS diseases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    treatment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for mapping symptoms to diseases with weight
CREATE TABLE IF NOT EXISTS symptom_disease_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symptom_id INT NOT NULL,
    disease_id INT NOT NULL,
    weight FLOAT NOT NULL DEFAULT 1.0,
    FOREIGN KEY (symptom_id) REFERENCES symptoms(id) ON DELETE CASCADE,
    FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE,
    UNIQUE KEY unique_symptom_disease (symptom_id, disease_id)
);

-- Insert sample symptoms
INSERT INTO symptoms (name, description, image) VALUES
('Vomiting', 'Forceful expulsion of stomach contents through the mouth', 'vomiting.jpg'),
('Diarrhea', 'Loose, watery stools occurring more frequently than usual', 'diarrhea.jpg'),
('Lethargy', 'Lack of energy, sluggishness, or unusual tiredness', 'lethargy.jpg'),
('Loss of Appetite', 'Reduced interest in food or refusal to eat', 'loss_appetite.jpg'),
('Excessive Thirst', 'Drinking more water than usual', 'excessive_thirst.jpg'),
('Coughing', 'Forceful expulsion of air from the lungs', 'coughing.jpg'),
('Sneezing', 'Involuntary expulsion of air from the nose', 'sneezing.jpg'),
('Itching/Scratching', 'Persistent scratching, licking, or biting at skin', 'itching.jpg'),
('Hair Loss', 'Patches of missing fur or general thinning of coat', 'hair_loss.jpg'),
('Limping', 'Favoring one or more limbs when walking', 'limping.jpg'),
('Bad Breath', 'Unusually foul-smelling breath', 'bad_breath.jpg'),
('Eye Discharge', 'Watery, mucus, or pus-like discharge from eyes', 'eye_discharge.jpg'),
('Ear Infection', 'Redness, swelling, discharge, or odor from ears', 'ear_infection.jpg'),
('Weight Loss', 'Unintended decrease in body weight', 'weight_loss.jpg'),
('Swelling', 'Abnormal enlargement of a body part or area', 'swelling.jpg');

-- Insert sample diseases/conditions
INSERT INTO diseases (name, description, treatment) VALUES
('Gastroenteritis', 'Inflammation of the stomach and intestines, typically resulting from infection or irritation. Common in pets who have eaten spoiled food or garbage.', 'Withhold food for 12-24 hours, provide small amounts of water, gradually reintroduce bland diet (boiled chicken and rice). Severe cases may require veterinary treatment with fluid therapy and medication.'),
('Kennel Cough', 'A highly contagious respiratory disease affecting dogs. It causes inflammation of the trachea and bronchi, resulting in a persistent, forceful cough.', 'Rest, humidified air, and cough suppressants for mild cases. Antibiotics may be prescribed for secondary bacterial infections. Severe cases may require veterinary care. Preventative vaccines are available.'),
('Ear Infection (Otitis)', 'Inflammation of the ear canal, often caused by bacteria, yeast, or allergies. More common in dogs with floppy ears.', 'Clean ears gently with veterinary-approved solution. Prescription ear drops containing antibiotics, antifungals, or anti-inflammatories. Addressing underlying allergies if present.'),
('Urinary Tract Infection', 'Bacterial infection affecting the bladder or urethra, causing painful and frequent urination.', 'Antibiotics prescribed by a veterinarian. Increased water intake to flush bacteria. Special diets may help prevent recurrence. Cranberry supplements sometimes recommended.'),
('Allergic Dermatitis', 'Skin inflammation caused by allergic reactions to environmental factors, food, or parasites.', 'Identify and remove allergen if possible. Antihistamines or corticosteroids to reduce inflammation. Medicated shampoos or topical treatments. Dietary changes for food allergies.'),
('Parvovirus', 'A highly contagious viral disease affecting primarily puppies and unvaccinated dogs, causing severe gastrointestinal symptoms.', 'Immediate veterinary care required. Intensive supportive care including IV fluids, anti-nausea medication, antibiotics for secondary infections. Isolation from other dogs. Preventative vaccination is essential.'),
('Diabetes Mellitus', 'Metabolic disorder characterized by insufficient insulin production or insulin resistance, leading to elevated blood sugar levels.', 'Daily insulin injections. Consistent feeding schedule with appropriate diet. Regular monitoring of blood glucose levels. Weight management and regular exercise.'),
('Arthritis', 'Inflammation of joints causing pain, stiffness, and reduced mobility. Common in older pets.', 'Anti-inflammatory medications. Joint supplements (glucosamine, chondroitin). Weight management to reduce joint stress. Physical therapy. Comfortable bedding and assistance with mobility.'),
('Dental Disease', 'Buildup of plaque and tartar leading to gingivitis, periodontitis, and tooth decay.', 'Professional dental cleaning under anesthesia. Daily tooth brushing with pet-safe toothpaste. Dental chews and toys. Special dental diets.'),
('Flea Infestation', 'Parasitic insects that feed on blood and cause irritation, allergic reactions, and can transmit diseases.', 'Prescription or over-the-counter flea treatments (topical, oral, or collar). Treating the environment (home, yard) to eliminate eggs and larvae. Regular use of preventative products.');

-- Create symptom-disease mappings with weights
INSERT INTO symptom_disease_mapping (symptom_id, disease_id, weight) VALUES
-- Gastroenteritis
(1, 1, 0.9), -- Vomiting
(2, 1, 0.9), -- Diarrhea
(3, 1, 0.6), -- Lethargy
(4, 1, 0.7), -- Loss of Appetite

-- Kennel Cough
(6, 2, 0.9), -- Coughing
(7, 2, 0.5), -- Sneezing
(3, 2, 0.4), -- Lethargy
(4, 2, 0.3), -- Loss of Appetite

-- Ear Infection
(13, 3, 0.9), -- Ear Infection
(8, 3, 0.5), -- Itching/Scratching
(3, 3, 0.2), -- Lethargy

-- Urinary Tract Infection
(5, 4, 0.8), -- Excessive Thirst
(3, 4, 0.4), -- Lethargy
(4, 4, 0.3), -- Loss of Appetite

-- Allergic Dermatitis
(8, 5, 0.9), -- Itching/Scratching
(9, 5, 0.7), -- Hair Loss
(12, 5, 0.4), -- Eye Discharge

-- Parvovirus
(1, 6, 0.9), -- Vomiting
(2, 6, 0.9), -- Diarrhea
(3, 6, 0.8), -- Lethargy
(4, 6, 0.8), -- Loss of Appetite
(14, 6, 0.7), -- Weight Loss

-- Diabetes Mellitus
(5, 7, 0.9), -- Excessive Thirst
(14, 7, 0.7), -- Weight Loss
(3, 7, 0.5), -- Lethargy
(4, 7, 0.3), -- Loss of Appetite

-- Arthritis
(10, 8, 0.9), -- Limping
(15, 8, 0.6), -- Swelling
(3, 8, 0.4), -- Lethargy

-- Dental Disease
(11, 9, 0.9), -- Bad Breath
(4, 9, 0.5), -- Loss of Appetite

-- Flea Infestation
(8, 10, 0.9), -- Itching/Scratching
(9, 10, 0.6), -- Hair Loss
(3, 10, 0.2); -- Lethargy