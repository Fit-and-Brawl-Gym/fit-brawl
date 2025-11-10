#!/bin/bash
# Script to check AWS Security Group port configuration

SERVER_IP="54.227.103.23"
SSH_KEY="/c/Users/Mikell Razon/Downloads/Mikell.pem"

echo "============================================"
echo "AWS EC2 Security Group Port Check"
echo "============================================"
echo ""

# Test port 80 (HTTP)
echo "🌐 Testing Port 80 (HTTP)..."
if curl -s --max-time 5 http://${SERVER_IP} > /dev/null 2>&1; then
    echo "✅ Port 80 is OPEN and accessible"
else
    echo "❌ Port 80 is BLOCKED or not responding"
fi
echo ""

# Test port 443 (HTTPS)
echo "🔒 Testing Port 443 (HTTPS)..."
if timeout 5 bash -c "echo > /dev/tcp/${SERVER_IP}/443" 2>/dev/null; then
    echo "✅ Port 443 is OPEN (SSL not configured yet)"
else
    echo "❌ Port 443 is BLOCKED - Need to open in AWS Security Group"
fi
echo ""

# Test port 22 (SSH)
echo "🔐 Testing Port 22 (SSH)..."
if timeout 5 bash -c "echo > /dev/tcp/${SERVER_IP}/22" 2>/dev/null; then
    echo "✅ Port 22 is OPEN (SSH working)"
else
    echo "❌ Port 22 is BLOCKED"
fi
echo ""

echo "============================================"
echo "Next Steps:"
echo "============================================"
if timeout 5 bash -c "echo > /dev/tcp/${SERVER_IP}/443" 2>/dev/null; then
    echo "✅ All required ports are open!"
    echo "   Ready for domain and SSL setup"
else
    echo "⚠️  Need to open port 443 in AWS Console:"
    echo ""
    echo "1. Go to: https://console.aws.amazon.com/ec2/"
    echo "2. Click 'Instances' → Select your instance"
    echo "3. Click 'Security' tab → Click security group name"
    echo "4. Click 'Edit inbound rules' → 'Add rule'"
    echo "5. Add:"
    echo "   Type: HTTPS"
    echo "   Port: 443"
    echo "   Source: 0.0.0.0/0"
    echo "6. Click 'Save rules'"
fi
echo ""
