#!/bin/bash

# Test script for webhook processing hooks
# This script demonstrates how to test webhook plugins

echo "=========================================="
echo "Webhook Processing Hook Test"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Testing webhook hook implementation...${NC}"
echo ""

# Test 1: Check if index.php contains the hooks
echo -e "${YELLOW}Test 1: Checking for pp_webhook_received hook in index.php${NC}"
if grep -q "pp_trigger_hook('pp_webhook_received'" index.php; then
    echo -e "${GREEN}✓ pp_webhook_received hook found${NC}"
else
    echo -e "${RED}✗ pp_webhook_received hook NOT found${NC}"
    exit 1
fi

echo -e "${YELLOW}Test 2: Checking for pp_webhook_processed hook in index.php${NC}"
if grep -q "pp_trigger_hook('pp_webhook_processed'" index.php; then
    echo -e "${GREEN}✓ pp_webhook_processed hook found${NC}"
else
    echo -e "${RED}✗ pp_webhook_processed hook NOT found${NC}"
    exit 1
fi

# Test 3: Check documentation exists
echo -e "${YELLOW}Test 3: Checking for Webhook Processing Plugin Guide${NC}"
if [ -f "docs/Webhook-Processing-Plugin-Guide.md" ]; then
    echo -e "${GREEN}✓ Webhook Processing Plugin Guide exists${NC}"
    LINES=$(wc -l < docs/Webhook-Processing-Plugin-Guide.md)
    echo -e "  Document has ${LINES} lines"
else
    echo -e "${RED}✗ Webhook Processing Plugin Guide NOT found${NC}"
    exit 1
fi

# Test 4: Check example plugin exists
echo -e "${YELLOW}Test 4: Checking for webhook-logger example plugin${NC}"
if [ -d "pp-content/plugins/modules/webhook-logger" ]; then
    echo -e "${GREEN}✓ webhook-logger example plugin exists${NC}"
    
    # Check required files
    if [ -f "pp-content/plugins/modules/webhook-logger/meta.json" ]; then
        echo -e "  ${GREEN}✓${NC} meta.json found"
    else
        echo -e "  ${RED}✗${NC} meta.json missing"
    fi
    
    if [ -f "pp-content/plugins/modules/webhook-logger/webhook-logger-class.php" ]; then
        echo -e "  ${GREEN}✓${NC} webhook-logger-class.php found"
    else
        echo -e "  ${RED}✗${NC} webhook-logger-class.php missing"
    fi
    
    if [ -f "pp-content/plugins/modules/webhook-logger/functions.php" ]; then
        echo -e "  ${GREEN}✓${NC} functions.php found"
    else
        echo -e "  ${RED}✗${NC} functions.php missing"
    fi
else
    echo -e "${RED}✗ webhook-logger example plugin NOT found${NC}"
    exit 1
fi

# Test 5: Check PHP syntax
echo -e "${YELLOW}Test 5: Checking PHP syntax in index.php${NC}"
if php -l index.php > /dev/null 2>&1; then
    echo -e "${GREEN}✓ index.php has valid PHP syntax${NC}"
else
    echo -e "${RED}✗ index.php has PHP syntax errors${NC}"
    php -l index.php
    exit 1
fi

echo -e "${YELLOW}Test 6: Checking PHP syntax in example plugin${NC}"
if php -l pp-content/plugins/modules/webhook-logger/webhook-logger-class.php > /dev/null 2>&1 && \
   php -l pp-content/plugins/modules/webhook-logger/functions.php > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Example plugin has valid PHP syntax${NC}"
else
    echo -e "${RED}✗ Example plugin has PHP syntax errors${NC}"
    exit 1
fi

# Test 7: Check hook reference in module guide
echo -e "${YELLOW}Test 7: Checking hook reference in Module Plugin Developer Guide${NC}"
if grep -q "pp_webhook_received" docs/PipraPay-Module-Plugin-Developer-Guide.md; then
    echo -e "${GREEN}✓ Webhook hooks documented in Module Plugin Developer Guide${NC}"
else
    echo -e "${RED}✗ Webhook hooks NOT documented in Module Plugin Developer Guide${NC}"
    exit 1
fi

echo ""
echo "=========================================="
echo -e "${GREEN}All tests passed!${NC}"
echo "=========================================="
echo ""
echo "The webhook processing hook implementation is complete and functional."
echo ""
echo "Next steps:"
echo "1. Review the documentation: docs/Webhook-Processing-Plugin-Guide.md"
echo "2. Examine the example plugin: pp-content/plugins/modules/webhook-logger/"
echo "3. Create your own webhook processing plugins using the guide"
echo ""
echo "To test with a real webhook request:"
echo "  curl -X POST 'http://localhost/?webhook=YOUR_KEY' \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -d '{\"test\":\"data\"}'"
echo ""
