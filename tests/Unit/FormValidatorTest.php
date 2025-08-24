<?php
/**
 * Form Validator Unit Tests
 * Appeal Prospect MVP - Form Validation Testing
 */

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

class FormValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../app/form_validator.php';
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testValidatorWithValidData(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '25',
            'website' => 'https://example.com',
            'bio' => 'This is a test bio'
        ];
        
        $rules = [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email',
            'age' => 'required|numeric|min:18|max:100',
            'website' => 'url',
            'bio' => 'required|min:10'
        ];
        
        $validator = new FormValidator($data, $rules);
        
        $this->assertTrue($validator->validate());
        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->getErrors());
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testValidatorWithInvalidData(): void
    {
        $data = [
            'name' => 'A', // Too short
            'email' => 'invalid-email', // Invalid format
            'age' => '15', // Too young
            'website' => 'not-a-url', // Invalid URL
            'bio' => 'Short' // Too short
        ];
        
        $rules = [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email',
            'age' => 'required|numeric|min:18|max:100',
            'website' => 'url',
            'bio' => 'required|min:10'
        ];
        
        $validator = new FormValidator($data, $rules);
        
        $this->assertFalse($validator->validate());
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
        
        $errors = $validator->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayHasKey('website', $errors);
        $this->assertArrayHasKey('bio', $errors);
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testRequiredValidation(): void
    {
        $testCases = [
            ['', false],
            ['   ', false],
            ['valid input', true],
            [null, false],
            [0, true],
            ['0', true]
        ];
        
        foreach ($testCases as [$value, $shouldPass]) {
            $validator = new FormValidator(['field' => $value], ['field' => 'required']);
            
            if ($shouldPass) {
                $this->assertTrue($validator->validate(), "Value '$value' should pass required validation");
            } else {
                $this->assertFalse($validator->validate(), "Value '$value' should fail required validation");
            }
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testEmailValidation(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
            'firstname-lastname@example.com'
        ];
        
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'test@',
            'test..test@example.com',
            'test@example'
        ];
        
        foreach ($validEmails as $email) {
            $validator = new FormValidator(['email' => $email], ['email' => 'email']);
            $this->assertTrue($validator->validate(), "Email '$email' should be valid");
        }
        
        foreach ($invalidEmails as $email) {
            $validator = new FormValidator(['email' => $email], ['email' => 'email']);
            $this->assertFalse($validator->validate(), "Email '$email' should be invalid");
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testMinMaxValidation(): void
    {
        // String length validation
        $validator = new FormValidator(['field' => 'test'], ['field' => 'min:5']);
        $this->assertFalse($validator->validate());
        
        $validator = new FormValidator(['field' => 'testing'], ['field' => 'min:5']);
        $this->assertTrue($validator->validate());
        
        $validator = new FormValidator(['field' => 'testing123'], ['field' => 'max:5']);
        $this->assertFalse($validator->validate());
        
        $validator = new FormValidator(['field' => 'test'], ['field' => 'max:5']);
        $this->assertTrue($validator->validate());
        
        // Numeric validation
        $validator = new FormValidator(['number' => '10'], ['number' => 'min:15']);
        $this->assertFalse($validator->validate());
        
        $validator = new FormValidator(['number' => '20'], ['number' => 'min:15']);
        $this->assertTrue($validator->validate());
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testBetweenValidation(): void
    {
        $testCases = [
            ['hello', 'between:3,10', true],
            ['hi', 'between:3,10', false],
            ['this is too long', 'between:3,10', false],
            ['5', 'between:1,10', true],
            ['15', 'between:1,10', false]
        ];
        
        foreach ($testCases as [$value, $rule, $shouldPass]) {
            $validator = new FormValidator(['field' => $value], ['field' => $rule]);
            
            if ($shouldPass) {
                $this->assertTrue($validator->validate(), "Value '$value' should pass '$rule' validation");
            } else {
                $this->assertFalse($validator->validate(), "Value '$value' should fail '$rule' validation");
            }
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testNumericValidation(): void
    {
        $validNumbers = ['123', '12.34', '0', '-5', '3.14159'];
        $invalidNumbers = ['abc', '12abc', '', 'not-a-number'];
        
        foreach ($validNumbers as $number) {
            $validator = new FormValidator(['number' => $number], ['number' => 'numeric']);
            $this->assertTrue($validator->validate(), "Value '$number' should be numeric");
        }
        
        foreach ($invalidNumbers as $number) {
            $validator = new FormValidator(['number' => $number], ['number' => 'numeric']);
            $this->assertFalse($validator->validate(), "Value '$number' should not be numeric");
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testIntegerValidation(): void
    {
        $validIntegers = ['123', '0', '-5', '999'];
        $invalidIntegers = ['12.34', 'abc', '12abc', '3.14159'];
        
        foreach ($validIntegers as $integer) {
            $validator = new FormValidator(['number' => $integer], ['number' => 'integer']);
            $this->assertTrue($validator->validate(), "Value '$integer' should be integer");
        }
        
        foreach ($invalidIntegers as $integer) {
            $validator = new FormValidator(['number' => $integer], ['number' => 'integer']);
            $this->assertFalse($validator->validate(), "Value '$integer' should not be integer");
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testUrlValidation(): void
    {
        $validUrls = [
            'https://example.com',
            'http://test.org',
            'https://sub.domain.com/path?query=1',
            'ftp://files.example.com'
        ];
        
        $invalidUrls = [
            'not-a-url',
            'example.com',
            'http://',
            'https://.com'
        ];
        
        foreach ($validUrls as $url) {
            $validator = new FormValidator(['url' => $url], ['url' => 'url']);
            $this->assertTrue($validator->validate(), "URL '$url' should be valid");
        }
        
        foreach ($invalidUrls as $url) {
            $validator = new FormValidator(['url' => $url], ['url' => 'url']);
            $this->assertFalse($validator->validate(), "URL '$url' should be invalid");
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testInValidation(): void
    {
        $validator = new FormValidator(['status' => 'active'], ['status' => 'in:active,inactive,pending']);
        $this->assertTrue($validator->validate());
        
        $validator = new FormValidator(['status' => 'invalid'], ['status' => 'in:active,inactive,pending']);
        $this->assertFalse($validator->validate());
        
        $validator = new FormValidator(['color' => 'red'], ['color' => 'in:red,green,blue']);
        $this->assertTrue($validator->validate());
        
        $validator = new FormValidator(['color' => 'yellow'], ['color' => 'in:red,green,blue']);
        $this->assertFalse($validator->validate());
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testConfirmedValidation(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ];
        $validator = new FormValidator($data, ['password_confirmation' => 'confirmed']);
        $this->assertTrue($validator->validate());
        
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'different'
        ];
        $validator = new FormValidator($data, ['password_confirmation' => 'confirmed']);
        $this->assertFalse($validator->validate());
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testFileValidation(): void
    {
        $validFile = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        $validator = new FormValidator(['upload' => $validFile], ['upload' => 'file']);
        $this->assertTrue($validator->validate());
        
        $invalidFile = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0
        ];
        
        $validator = new FormValidator(['upload' => $invalidFile], ['upload' => 'file']);
        $this->assertFalse($validator->validate());
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testMimesValidation(): void
    {
        $pdfFile = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => TestUtils::createTempFile('%PDF-1.4'),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        $validator = new FormValidator(['file' => $pdfFile], ['file' => 'mimes:application/pdf,text/plain']);
        $this->assertTrue($validator->validate());
        
        $invalidFile = [
            'name' => 'test.exe',
            'type' => 'application/executable',
            'tmp_name' => TestUtils::createTempFile('executable'),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        $validator = new FormValidator(['file' => $invalidFile], ['file' => 'mimes:application/pdf,text/plain']);
        $this->assertFalse($validator->validate());
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testPasswordValidation(): void
    {
        $strongPasswords = [
            'Password123!',
            'MySecure$Password1',
            'C0mpl3xP@ssw0rd'
        ];
        
        $weakPasswords = [
            'password',
            '123456',
            'Password',
            'password123',
            'Pass123'
        ];
        
        foreach ($strongPasswords as $password) {
            $validator = new FormValidator(['password' => $password], ['password' => 'password']);
            $this->assertTrue($validator->validate(), "Password '$password' should be valid");
        }
        
        foreach ($weakPasswords as $password) {
            $validator = new FormValidator(['password' => $password], ['password' => 'password']);
            $this->assertFalse($validator->validate(), "Password '$password' should be invalid");
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testCaseNameValidation(): void
    {
        $validCaseNames = [
            'Smith v Jones 2024',
            'ABC Corp vs XYZ Ltd',
            'Re: Estate of John Doe',
            'Criminal Case 123/2024'
        ];
        
        $invalidCaseNames = [
            'AB', // Too short
            '<script>alert("xss")</script>', // XSS attempt
            'Case with invalid chars: <>"|\\',
            str_repeat('A', 256) // Too long
        ];
        
        foreach ($validCaseNames as $caseName) {
            $validator = new FormValidator(['case_name' => $caseName], ['case_name' => 'casename']);
            $this->assertTrue($validator->validate(), "Case name '$caseName' should be valid");
        }
        
        foreach ($invalidCaseNames as $caseName) {
            $validator = new FormValidator(['case_name' => $caseName], ['case_name' => 'casename']);
            $this->assertFalse($validator->validate(), "Case name '$caseName' should be invalid");
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testNameValidation(): void
    {
        $validNames = [
            'John Doe',
            'Mary Jane Smith',
            "O'Connor",
            'Jean-Pierre'
        ];
        
        $invalidNames = [
            'A', // Too short
            'John123', // Contains numbers
            'John@Doe', // Contains symbols
            str_repeat('A', 101) // Too long
        ];
        
        foreach ($validNames as $name) {
            $validator = new FormValidator(['name' => $name], ['name' => 'name']);
            $this->assertTrue($validator->validate(), "Name '$name' should be valid");
        }
        
        foreach ($invalidNames as $name) {
            $validator = new FormValidator(['name' => $name], ['name' => 'name']);
            $this->assertFalse($validator->validate(), "Name '$name' should be invalid");
        }
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testCustomMessages(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        $messages = [
            'required' => 'The :field field is mandatory',
            'name.required' => 'Please enter your name'
        ];
        
        $validator = new FormValidator($data, $rules, $messages);
        $validator->validate();
        
        $errors = $validator->getErrors();
        $this->assertStringContainsString('Please enter your name', $errors['name'][0]);
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testMultipleRulesOnSameField(): void
    {
        $data = ['email' => 'a']; // Too short and invalid format
        $rules = ['email' => 'required|email|min:5'];
        
        $validator = new FormValidator($data, $rules);
        $this->assertFalse($validator->validate());
        
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('email', $errors);
        // Should fail on first rule that fails (email format)
        $this->assertCount(1, $errors['email']);
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testValidationAttributesHelper(): void
    {
        $rules = ['required', 'email', 'min:5', 'max:50'];
        $attributes = validation_attributes($rules);
        
        $this->assertStringContainsString('required', $attributes);
        $this->assertStringContainsString('type="email"', $attributes);
        $this->assertStringContainsString('minlength="5"', $attributes);
        $this->assertStringContainsString('maxlength="50"', $attributes);
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testValidationHelperFunctions(): void
    {
        $errors = [
            'name' => ['Name is required'],
            'email' => ['Email is invalid', 'Email is too short']
        ];
        
        // Test has_error function
        $this->assertTrue(has_error($errors, 'name'));
        $this->assertTrue(has_error($errors, 'email'));
        $this->assertFalse(has_error($errors, 'phone'));
        
        // Test get_error function
        $this->assertEquals('Name is required', get_error($errors, 'name'));
        $this->assertEquals('Email is invalid', get_error($errors, 'email'));
        $this->assertEquals('', get_error($errors, 'phone'));
        
        // Test error_class function
        $this->assertEquals('is-invalid', error_class($errors, 'name'));
        $this->assertEquals('is-invalid', error_class($errors, 'email'));
        $this->assertEquals('', error_class($errors, 'phone'));
    }
    
    /**
     * @group validation
     * @group forms
     */
    public function testValidatorHelperFunction(): void
    {
        $data = ['name' => 'John Doe'];
        $rules = ['name' => 'required|min:2'];
        
        $validator = validator($data, $rules);
        $this->assertInstanceOf(FormValidator::class, $validator);
        $this->assertTrue($validator->validate());
    }
    
    /**
     * @group validation
     * @group performance
     */
    public function testValidationPerformance(): void
    {
        $data = array_fill_keys(range(0, 100), 'test@example.com');
        $rules = array_fill_keys(range(0, 100), 'required|email');
        
        $this->assertExecutionTime(function() use ($data, $rules) {
            $validator = new FormValidator($data, $rules);
            $validator->validate();
        }, 1.0);
    }
}