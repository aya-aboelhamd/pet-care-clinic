<?php
// controllers/TriageController.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // الخطوة الأولى: تحليل العرض المبدئي
    if (isset($_POST['action']) && $_POST['action'] == 'analyze_symptom') {
        $symptom = strtolower($_POST['symptom']);
        
        // Logic Gate 1: Oncology (الأورام)
        if (strpos($symptom, 'lump') !== false || strpos($symptom, 'mass') !== false || strpos($symptom, 'tumor') !== false) {
            echo json_encode([
                'step' => 'follow_up', 
                'context' => 'oncology',
                'question' => 'Is the lump growing rapidly, bleeding, or changing color?'
            ]);
            exit();
        }
        
        // Logic Gate 2: Behavior (تعديل السلوك)
        if (strpos($symptom, 'bite') !== false || strpos($symptom, 'aggressive') !== false || strpos($symptom, 'scared') !== false) {
            echo json_encode([
                'step' => 'follow_up', 
                'context' => 'behavior',
                'question' => 'Did this behavior start suddenly after a specific event or trauma?'
            ]);
            exit();
        }

        // Logic Gate 3: Orthopedics (العظام)
        if (strpos($symptom, 'limp') !== false || strpos($symptom, 'walk') !== false || strpos($symptom, 'leg') !== false) {
            echo json_encode([
                'step' => 'follow_up', 
                'context' => 'orthopedic',
                'question' => 'Did the pet fall, jump from a high place, or have a recent accident?'
            ]);
            exit();
        }

        // Default Gate: General Vet
        echo json_encode([
            'step' => 'result', 
            'specialist' => 'General Veterinarian',
            'rationale' => 'Based on the symptoms provided, a general physical examination is the best starting point to diagnose the issue.'
        ]);
        exit();
    }

    // الخطوة الثانية: الإجابة على السؤال الإضافي (Follow-up)
    if (isset($_POST['action']) && $_POST['action'] == 'follow_up') {
        $context = $_POST['context'];
        $answer = $_POST['answer']; // 'yes' or 'no'

        if ($context == 'oncology') {
            if ($answer == 'yes') {
                echo json_encode(['step' => 'result', 'specialist' => 'Veterinary Oncologist', 'rationale' => 'Rapidly growing or changing lumps are red flags and require immediate specialized cancer screening and biopsy.']);
            } else {
                echo json_encode(['step' => 'result', 'specialist' => 'General Veterinarian', 'rationale' => 'Stable lumps should still be checked, but a general vet can perform the initial assessment.']);
            }
        } 
        elseif ($context == 'behavior') {
            if ($answer == 'yes') {
                echo json_encode(['step' => 'result', 'specialist' => 'Veterinary Behaviorist', 'rationale' => 'Sudden behavioral changes often require a specialist to address underlying psychological trauma or neurological issues.']);
            } else {
                echo json_encode(['step' => 'result', 'specialist' => 'General Veterinarian', 'rationale' => 'Gradual behavioral issues can sometimes be linked to pain or illness. A general vet should rule out medical causes first.']);
            }
        }
        elseif ($context == 'orthopedic') {
            if ($answer == 'yes') {
                echo json_encode(['step' => 'result', 'specialist' => 'Veterinary Orthopedic Surgeon', 'rationale' => 'Trauma resulting in limping indicates potential fractures or ligament tears requiring specialized surgical evaluation.']);
            } else {
                echo json_encode(['step' => 'result', 'specialist' => 'General Veterinarian', 'rationale' => 'Limping without trauma could be arthritis or a minor sprain. A general vet can prescribe anti-inflammatories.']);
            }
        }
        exit();
    }
}
?>