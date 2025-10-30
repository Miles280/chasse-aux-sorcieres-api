<?php

namespace App\DataFixtures;

use App\Entity\Camp;
use App\Entity\Alignment;
use App\Entity\Goal;
use App\Entity\Role;
use App\Entity\Power;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1️⃣ Charger le contenu JSON
        $filePath = __DIR__ . '/roles_fixtures.json';
        $data = json_decode(file_get_contents($filePath), true);

        // On garde des références pour lier les entités entre elles
        $refs = [
            'Camp' => [],
            'Alignment' => [],
            'Goal' => [],
            'Role' => [],
            'Power' => []
        ];

        // 2️⃣ Camps
        foreach ($data['Camp'] as $item) {
            $camp = new Camp();
            $camp->setName($item['name']);
            $camp->setColor($item['color'] ?? null);
            $camp->setEmojiName($item['emojiName'] ?? null);
            $camp->setEmojiId($item['emojiId'] ?? null);
            $camp->setDescription($item['description'] ?? null);

            $manager->persist($camp);
            $refs['Camp'][$item['id']] = $camp;
        }

        // 3️⃣ Alignments
        foreach ($data['Alignment'] as $item) {
            $alignment = new Alignment();
            $alignment->setName($item['name']);
            $manager->persist($alignment);
            $refs['Alignment'][$item['id']] = $alignment;
        }

        // 4️⃣ Goals
        foreach ($data['Goal'] as $item) {
            $goal = new Goal();
            $goal->setObjective($item['objective']);
            $manager->persist($goal);
            $refs['Goal'][$item['id']] = $goal;
        }

        // 5️⃣ Roles
        foreach ($data['Role'] as $item) {
            $role = new Role();
            $role->setName($item['name']);
            $role->setDescription($item['description']);
            $role->setMinPlayer($item['minPlayer']);
            
            // Camp
            if (isset($item['camp'])) {
                $campId = (int) basename($item['camp']);
                $role->setCamp($refs['Camp'][$campId]);
            }

            // Goal (peut être null)
            if (!empty($item['goal'])) {
                $goalId = (int) basename($item['goal']);
                $role->setGoal($refs['Goal'][$goalId]);
            }

            // Alignments
            foreach ($item['alignment'] as $alignmentIri) {
                $alignmentId = (int) basename($alignmentIri);
                $role->addAlignment($refs['Alignment'][$alignmentId]);
            }

            $manager->persist($role);
            $refs['Role'][$item['id']] = $role;
        }

        // 6️⃣ Powers
        foreach ($data['Power'] as $item) {
            $power = new Power();
            $power->setTitle($item['title']);
            $power->setDescription($item['description']);
            $power->setIsDayPower($item['isDayPower']);
            $power->setIsPassive($item['isPassive']);
            $power->setUsageLimit($item['usageLimit']);
            $power->setPosition($item['position']);
            $power->setLeavingHouse($item['leavingHouse']);
            
            // Lier le rôle
            $roleId = (int) basename($item['role']);
            $power->setRole($refs['Role'][$roleId]);

            $manager->persist($power);
        }

        // 7️⃣ Flush final
        $manager->flush();

        echo "✅ Fixtures importées depuis roles_fixtures.json avec succès !\n";
    }
}
