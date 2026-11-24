<s:form:create in="contact">
    <input type="email" name="email">
    <s:forms_captcha :fields="$fields" />
    <button>Send</button>
</s:form:create>
