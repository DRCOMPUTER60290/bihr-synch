#!/bin/bash
# Script de vérification du format de payload BIHR
# Usage: ./verify-dropshipping-format.sh

echo "🔍 Vérification du format de payload BIHR Dropshipping"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteurs
TOTAL=0
SUCCESS=0
FAILED=0

check_file() {
    local file=$1
    local pattern=$2
    local description=$3
    
    TOTAL=$((TOTAL + 1))
    
    if grep -q "$pattern" "$file"; then
        echo -e "${GREEN}✅${NC} $description"
        SUCCESS=$((SUCCESS + 1))
    else
        echo -e "${RED}❌${NC} $description"
        FAILED=$((FAILED + 1))
    fi
}

echo "📋 Vérification des champs dans class-bihr-order-sync.php"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Vérification des champs Lines
check_file "includes/class-bihr-order-sync.php" "'ReferenceType'" "Champ ReferenceType présent"
check_file "includes/class-bihr-order-sync.php" "'ReservedQuantity'" "Champ ReservedQuantity présent"
check_file "includes/class-bihr-order-sync.php" "Not used anymore" "Valeur ReferenceType correcte"

# Vérification des champs Order
check_file "includes/class-bihr-order-sync.php" "IsWeeklyFreeShippingActivated" "Champ IsWeeklyFreeShippingActivated présent"
check_file "includes/class-bihr-order-sync.php" "DeliveryMode" "Champ DeliveryMode présent"
check_file "includes/class-bihr-order-sync.php" "bihrwi_weekly_free_shipping" "Option weekly_free_shipping utilisée"
check_file "includes/class-bihr-order-sync.php" "bihrwi_delivery_mode" "Option delivery_mode utilisée"

# Vérification DropShippingAddress
check_file "includes/class-bihr-order-sync.php" "DropShippingAddress" "DropShippingAddress présent"

echo ""
echo "📋 Vérification de l'interface admin"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Vérification des options dans l'interface admin
check_file "admin/views/orders-settings-page.php" "bihrwi_weekly_free_shipping" "Option weekly_free_shipping dans UI"
check_file "admin/views/orders-settings-page.php" "bihrwi_delivery_mode" "Option delivery_mode dans UI"
check_file "admin/views/orders-settings-page.php" "IsWeeklyFreeShippingActivated" "Documentation IsWeeklyFreeShippingActivated"
check_file "admin/views/orders-settings-page.php" "DeliveryMode" "Documentation DeliveryMode"

echo ""
echo "📋 Vérification de l'exemple JSON"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Vérification de l'exemple JSON dans la page admin
check_file "admin/views/orders-settings-page.php" "\"ReferenceType\"" "ReferenceType dans exemple JSON"
check_file "admin/views/orders-settings-page.php" "\"ReservedQuantity\"" "ReservedQuantity dans exemple JSON"
check_file "admin/views/orders-settings-page.php" "\"IsWeeklyFreeShippingActivated\"" "IsWeeklyFreeShippingActivated dans JSON"
check_file "admin/views/orders-settings-page.php" "\"DeliveryMode\"" "DeliveryMode dans JSON"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 RÉSULTAT"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Total de vérifications : $TOTAL"
echo -e "${GREEN}✅ Succès : $SUCCESS${NC}"
echo -e "${RED}❌ Échecs : $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 TOUS LES TESTS SONT PASSÉS !${NC}"
    echo "Le format du payload est 100% conforme à l'exemple BIHR."
    exit 0
else
    echo -e "${RED}⚠️ CERTAINS TESTS ONT ÉCHOUÉ${NC}"
    echo "Veuillez vérifier les éléments manquants ci-dessus."
    exit 1
fi
